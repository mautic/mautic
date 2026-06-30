<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\EventListener;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

class LeadSubscriberFunctionalTest extends MauticMysqlTestCase
{
    /**
     * Regression test: a contact with an asset_downloads row whose asset_id is NULL
     * (e.g. the asset has been deleted while history is preserved) must not crash
     * the timeline. The orphaned download should still appear, just without a
     * download link or preview.
     */
    public function testTimelineRendersOrphanedDownloadWithoutLink(): void
    {
        [$leadId, $downloadId] = $this->seedDownload('Soon-to-be-deleted asset', 'orphan-asset');

        // Simulate the "asset deleted, download history kept" state by detaching
        // the asset reference at the SQL level (bypasses the ORM cascade).
        $this->em->getConnection()->executeStatement(
            'UPDATE '.MAUTIC_TABLE_PREFIX.'asset_downloads SET asset_id = NULL WHERE id = :id',
            ['id' => $downloadId]
        );
        $this->em->clear();

        $event = $this->getFirstAssetDownloadEvent($leadId);

        // When the asset is gone, eventLabel is a plain string rather than the
        // {label, href} array used for live assets — this prevents the Lead
        // timeline twig from erroring on a missing href key. The DownloadRepository
        // LEFT JOINs assets, so a.title resolves to NULL once asset_id is NULL;
        // the subscriber falls back to a localized "Deleted asset" placeholder.
        self::assertIsString($event['eventLabel']);
        self::assertNotEmpty($event['eventLabel']);
        self::assertNull($event['extra']['asset']);
        self::assertNull($event['extra']['assetDownloadUrl']);
    }

    /**
     * Sanity check: a download row referencing a still-existing asset renders with
     * the linked label and a generated download URL.
     */
    public function testTimelineRendersLiveDownloadWithLink(): void
    {
        [$leadId] = $this->seedDownload('Live asset', 'live-asset');
        $this->em->clear();

        $event = $this->getFirstAssetDownloadEvent($leadId);

        self::assertIsArray($event['eventLabel']);
        self::assertArrayHasKey('href', $event['eventLabel']);
        self::assertSame('Live asset', $event['eventLabel']['label']);
        self::assertInstanceOf(Asset::class, $event['extra']['asset']);
        self::assertNotNull($event['extra']['assetDownloadUrl']);
    }

    /**
     * @return array{0: int, 1: int} [leadId, downloadId]
     */
    private function seedDownload(string $assetTitle, string $assetAlias): array
    {
        $lead = new Lead();
        $lead->setFirstname('Timeline');
        $lead->setLastname('Tester');
        $this->em->persist($lead);

        $asset = new Asset();
        $asset->setTitle($assetTitle);
        $asset->setAlias($assetAlias);
        $asset->setStorageLocation('local');
        $asset->setPath('placeholder.txt');
        $asset->setExtension('txt');
        $asset->setSize(0);
        $this->em->persist($asset);
        $this->em->flush();

        $download = new Download();
        $download->setAsset($asset);
        $download->setLead($lead);
        $download->setDateDownload(new \DateTime('2026-01-01 00:00:00'));
        $download->setCode(200);
        $download->setTrackingId((string) random_int(1, 99999));
        $this->em->persist($download);
        $this->em->flush();

        return [(int) $lead->getId(), (int) $download->getId()];
    }

    /**
     * @return array<string, mixed>
     */
    private function getFirstAssetDownloadEvent(int $leadId): array
    {
        /** @var LeadModel $leadModel */
        $leadModel = static::getContainer()->get('mautic.lead.model.lead');
        $payload   = $leadModel->getEngagements($leadModel->getEntity($leadId), []);

        // LeadTimelineEvent::getEvents() flattens events across all types into
        // a single indexed array, so filter by the event key here.
        $assetDownloadEvents = array_values(array_filter(
            $payload['events'],
            static fn (array $event): bool => 'asset.download' === ($event['event'] ?? null),
        ));
        self::assertCount(1, $assetDownloadEvents);

        return $assetDownloadEvents[0];
    }
}
