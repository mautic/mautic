<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * An asset's own download tracking setting wins over the global one, but never over a privacy signal.
 */
final class AssetDownloadTrackingOverrideFunctionalTest extends MauticMysqlTestCase
{
    private const GLOBAL_OFF = 'global tracking off';

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['track_asset_downloads'] = !str_contains((string) $this->dataName(), self::GLOBAL_OFF);
        $this->configParams['aggregate_analytics']   = true;

        parent::setUp();
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function trackingProvider(): iterable
    {
        yield 'use global, global tracking on' => [Asset::TRACK_DOWNLOADS_GLOBAL, true];
        yield 'use global, '.self::GLOBAL_OFF => [Asset::TRACK_DOWNLOADS_GLOBAL, false];
        yield 'always, global tracking on' => [Asset::TRACK_DOWNLOADS_ALWAYS, true];
        yield 'always, '.self::GLOBAL_OFF => [Asset::TRACK_DOWNLOADS_ALWAYS, true];
        yield 'never, global tracking on' => [Asset::TRACK_DOWNLOADS_NEVER, false];
        yield 'never, '.self::GLOBAL_OFF => [Asset::TRACK_DOWNLOADS_NEVER, false];
    }

    #[DataProvider('trackingProvider')]
    public function testAssetSettingDecidesWhetherTheDownloadIsLinkedToTheContact(string $trackDownloads, bool $linked): void
    {
        $asset   = $this->createAsset($trackDownloads);
        $contact = $this->createContact();

        $this->download($asset, $contact, []);

        $this->assertSame($linked ? [$contact->getId()] : [], $this->getDownloadContactIds($asset));
        $this->assertSame(1, $this->getDownloadCount($asset), 'the download is counted either way');
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function privacySignalProvider(): iterable
    {
        yield 'Do Not Track' => [['HTTP_DNT' => '1']];
        yield 'Global Privacy Control' => [['HTTP_SEC_GPC' => '1']];
    }

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('privacySignalProvider')]
    public function testAlwaysTrackDoesNotOverridePrivacySignals(array $server): void
    {
        $asset   = $this->createAsset(Asset::TRACK_DOWNLOADS_ALWAYS);
        $contact = $this->createContact();

        $this->download($asset, $contact, $server);

        $this->assertSame([], $this->getDownloadContactIds($asset));
        $this->assertSame(1, $this->getDownloadCount($asset));
    }

    /**
     * @param array<string, string> $server
     */
    private function download(Asset $asset, Lead $contact, array $server): void
    {
        $this->client->followRedirects(false);
        $this->client->request(
            Request::METHOD_GET,
            '/asset/'.$asset->getSlug(),
            ['ct' => ClickthroughHelper::encodeArrayForUrl(['lead' => $contact->getId()])],
            [],
            $server
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND, 'the visitor gets the file either way');
    }

    /**
     * @return list<int>
     */
    private function getDownloadContactIds(Asset $asset): array
    {
        $downloads = $this->em->getRepository(Download::class)->findBy(['asset' => $asset->getId()]);

        return array_values(array_filter(array_map(static fn (Download $download): ?int => $download->getLead()?->getId(), $downloads)));
    }

    private function getDownloadCount(Asset $asset): int
    {
        $this->em->clear();

        return (int) $this->em->find(Asset::class, $asset->getId())->getDownloadCount();
    }

    private function createAsset(string $trackDownloads): Asset
    {
        $asset = new Asset();
        $asset->setTitle('Override asset');
        $asset->setAlias('override-asset');
        $asset->setStorageLocation('remote');
        $asset->setRemotePath('https://example.com/override.pdf');
        $asset->setIsPublished(true);
        $asset->setTrackDownloads($trackDownloads);
        $this->em->persist($asset);
        $this->em->flush();

        return $asset;
    }

    private function createContact(): Lead
    {
        $contact = new Lead();
        $contact->setEmail('override@example.com');
        $this->em->persist($contact);
        $this->em->flush();

        $this->logoutUser();

        return $contact;
    }
}
