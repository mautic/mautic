<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * With download tracking off, a download is still served and counted, but nothing links it to a contact.
 */
final class AssetDownloadTrackingSettingFunctionalTest extends MauticMysqlTestCase
{
    private const VISITOR_IP = '203.0.113.30';

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['track_asset_downloads'] = !str_contains($this->name(), 'TrackingOff');
        $this->configParams['aggregate_analytics']   = true;

        parent::setUp();
    }

    public function testDownloadFromEmailLinkIsRecordedOnTheContact(): void
    {
        $asset   = $this->createAsset();
        $contact = $this->createContact();

        $this->download($asset, ['ct' => ClickthroughHelper::encodeArrayForUrl(['lead' => $contact->getId()])]);

        $this->assertCounts($asset, 1, 1);
        $this->assertSame([$contact->getId()], $this->getDownloadContactIds($asset));
    }

    public function testDownloadFromEmailLinkIsNotLinkedWhileTrackingOff(): void
    {
        $asset   = $this->createAsset();
        $contact = $this->createContact();

        $this->download($asset, ['ct' => ClickthroughHelper::encodeArrayForUrl(['lead' => $contact->getId()])], ['REMOTE_ADDR' => self::VISITOR_IP]);

        $this->assertCounts($asset, 1, 0);
        $this->assertSame([], $this->getDownloadContactIds($asset));
        $this->assertNoTrackingCookieSet();
        $this->assertNotInstanceOf(IpAddress::class, $this->em->getRepository(IpAddress::class)->findOneBy(['ipAddress' => self::VISITOR_IP]), 'the visitor\'s IP address is not stored');
    }

    public function testDownloadFromTrackedVisitorIsRecordedOnTheirContact(): void
    {
        $asset   = $this->createAsset();
        $contact = $this->createTrackedVisitor();

        $this->download($asset);

        $this->assertCounts($asset, 1, 1);
        $this->assertSame([$contact->getId()], $this->getDownloadContactIds($asset));
    }

    public function testDownloadFromTrackedVisitorIsNotLinkedWhileTrackingOff(): void
    {
        $asset = $this->createAsset();
        $this->createTrackedVisitor();

        $this->download($asset);

        $this->assertCounts($asset, 1, 0);
        $this->assertSame([], $this->getDownloadContactIds($asset));
        // The existing cookies are neither used nor refreshed
        $this->assertNoTrackingCookieSet();
    }

    /**
     * @param array<string, string> $query
     * @param array<string, string> $server
     */
    private function download(Asset $asset, array $query = [], array $server = []): void
    {
        $this->client->followRedirects(false);
        $this->client->request(Request::METHOD_GET, '/asset/'.$asset->getSlug(), $query, [], $server);
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

    /**
     * @return list<int>
     */
    private function getDownloadContactIds(Asset $asset): array
    {
        $downloads = $this->em->getRepository(Download::class)->findBy(['asset' => $asset->getId()]);

        return array_values(array_filter(array_map(static fn (Download $download): ?int => $download->getLead()?->getId(), $downloads)));
    }

    private function assertNoTrackingCookieSet(): void
    {
        $cookies = array_map(static fn (\Symfony\Component\HttpFoundation\Cookie $cookie): string => $cookie->getName(), $this->client->getResponse()->headers->getCookies());

        $this->assertNotContains('mautic_device_id', $cookies);
        $this->assertNotContains('mtc_id', $cookies);
    }

    private function createAsset(): Asset
    {
        $asset = new Asset();
        $asset->setTitle('Tracked asset');
        $asset->setAlias('tracked-asset');
        $asset->setStorageLocation('remote');
        $asset->setRemotePath('https://example.com/tracked.pdf');
        $asset->setIsPublished(true);
        $this->em->persist($asset);
        $this->em->flush();

        return $asset;
    }

    private function createContact(): Lead
    {
        $contact = new Lead();
        $contact->setEmail('downloader@example.com');
        $this->em->persist($contact);
        $this->em->flush();

        $this->logoutUser();

        return $contact;
    }

    private function createTrackedVisitor(): Lead
    {
        $contact = new Lead();
        $contact->setFirstname('Trackedvisitor');
        $this->em->persist($contact);

        $device = new LeadDevice();
        $device->setLead($contact);
        $device->setTrackingId('assettrackingid000000000');
        $device->setDateAdded(new \DateTime());
        $this->em->persist($device);
        $this->em->flush();

        $this->logoutUser();
        $cookieJar = $this->client->getCookieJar();
        $cookieJar->set(new Cookie('mautic_device_id', 'assettrackingid000000000'));
        $cookieJar->set(new Cookie('mtc_id', (string) $contact->getId()));

        return $contact;
    }
}
