<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Each asset decides whether its downloads are tracked. New assets start from the global setting,
 * and a privacy signal always wins.
 */
final class AssetDownloadTrackingOverrideFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['track_asset_downloads'] = !str_contains($this->name(), 'GlobalOff');
        $this->configParams['aggregate_analytics']   = true;

        parent::setUp();
    }

    public function testAssetToggledOffIsNotLinkedWhileGlobalIsOn(): void
    {
        $asset   = $this->createAsset(false);
        $contact = $this->createContact();

        $this->download($asset, $contact, []);

        $this->assertSame([], $this->getDownloadContactIds($asset));
        $this->assertSame(1, $this->getDownloadCount($asset), 'the download is counted either way');
    }

    public function testAssetToggledOnIsLinkedWhileGlobalOff(): void
    {
        $asset   = $this->createAsset(true);
        $contact = $this->createContact();

        $this->download($asset, $contact, []);

        $this->assertSame([$contact->getId()], $this->getDownloadContactIds($asset));
        $this->assertSame(1, $this->getDownloadCount($asset));
    }

    public function testNewAssetStartsFromTheGlobalSetting(): void
    {
        $this->assertTrue($this->getNewAsset()->getTrackDownloads());
        $this->assertSame('1', $this->getNewAssetFormValue());
    }

    public function testNewAssetStartsFromTheGlobalSettingWhileGlobalOff(): void
    {
        $this->assertFalse($this->getNewAsset()->getTrackDownloads());
        $this->assertSame('0', $this->getNewAssetFormValue());
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
    public function testAssetToggledOnDoesNotOverridePrivacySignals(array $server): void
    {
        $asset   = $this->createAsset(true);
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

    private function getNewAsset(): Asset
    {
        $asset = self::getContainer()->get(AssetModel::class)->getEntity();
        $this->assertInstanceOf(Asset::class, $asset);

        return $asset;
    }

    private function getNewAssetFormValue(): string
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/assets/new');
        $this->assertResponseIsSuccessful();

        return (string) $crawler->filter('input[name="asset[trackDownloads]"]:checked')->attr('value');
    }

    private function createAsset(bool $trackDownloads): Asset
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
