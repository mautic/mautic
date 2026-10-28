<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Download counts are aggregate analytics: they never depend on the visitor being tracked, and nothing links them to the visitor.
 */
final class AssetDownloadCountFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['aggregate_analytics'] = !str_contains($this->name(), 'AggregateAnalyticsOff');

        parent::setUp();
    }

    public function testTrackedDownloadIsCountedAndRecordedOnTheContact(): void
    {
        $asset = $this->createAsset();

        $this->download($asset, []);

        $this->assertCounts($asset, 1, 1);
        $downloads = $this->em->getRepository(Download::class)->findBy(['asset' => $asset->getId()]);
        $this->assertCount(1, $downloads);
        $this->assertInstanceOf(\Mautic\LeadBundle\Entity\Lead::class, $downloads[0]->getLead());
        $this->assertInstanceOf(Cookie::class, $this->client->getCookieJar()->get('mautic_device_id'));
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function untrackableRequestProvider(): iterable
    {
        yield 'Do Not Track' => [['HTTP_DNT' => '1']];
        yield 'Global Privacy Control' => [['HTTP_SEC_GPC' => '1']];
    }

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('untrackableRequestProvider')]
    public function testUntrackedDownloadIsCountedWithoutLinkingItToTheVisitor(array $server): void
    {
        $asset = $this->createAsset();

        $this->download($asset, $server);

        // Without an identifier it can't count as unique
        $this->assertCounts($asset, 1, 0);
        $this->assertCount(0, $this->em->getRepository(Download::class)->findBy(['asset' => $asset->getId()]));
        $this->assertNotInstanceOf(Cookie::class, $this->client->getCookieJar()->get('mautic_device_id'));
        $this->assertNotInstanceOf(Cookie::class, $this->client->getCookieJar()->get('mtc_id'));
    }

    public function testDownloadsAreNotCountedWhileAggregateAnalyticsOff(): void
    {
        $asset = $this->createAsset();

        $this->download($asset, []);
        $this->download($asset, ['HTTP_DNT' => '1']);

        $this->assertCounts($asset, 0, 0);
        // Recording the download on the contact is tracking, not aggregate analytics, so it doesn't change
        $this->assertCount(1, $this->em->getRepository(Download::class)->findBy(['asset' => $asset->getId()]));
    }

    /**
     * @param array<string, string> $server
     */
    private function download(Asset $asset, array $server): void
    {
        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, '/asset/'.$asset->getSlug(), [], [], $server);
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND, 'the visitor gets the file either way');
        $this->assertSame($asset->getRemotePath(), $this->client->getResponse()->headers->get('Location'));
    }

    private function assertCounts(Asset $asset, int $total, int $unique): void
    {
        $this->em->clear();
        $asset = $this->em->find(Asset::class, $asset->getId());
        $this->assertInstanceOf(Asset::class, $asset);

        $this->assertSame($total, (int) $asset->getDownloadCount(), 'download count');
        $this->assertSame($unique, (int) $asset->getUniqueDownloadCount(), 'unique download count');
    }

    private function createAsset(): Asset
    {
        $asset = new Asset();
        $asset->setTitle('Counted asset');
        $asset->setAlias('counted-asset');
        $asset->setStorageLocation('remote');
        $asset->setRemotePath('https://example.com/counted.pdf');
        $asset->setIsPublished(true);
        $this->em->persist($asset);
        $this->em->flush();

        $this->logoutUser();

        return $asset;
    }
}
