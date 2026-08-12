<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Model;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('database')]
final class AssetModelFunctionalTest extends MauticMysqlTestCase
{
    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement(['assets']);
    }

    /**
     * @param array<string, string> $clickthrough
     */
    #[DataProvider('generateUrlDataProvider')]
    public function testGenerateUrl(
        string $assetAlias,
        bool $absolute,
        array $clickthrough,
        ?string $stream,
        string $expectedQuery,
    ): void {
        $asset = new Asset();
        $asset->setTitle($assetAlias);
        $asset->setAlias($assetAlias);
        $asset->setDateAdded(new \DateTime());
        $asset->setDateModified(new \DateTime());
        $asset->setCreatedByUser('User');
        $asset->setStorageLocation('remote');
        $asset->setRemotePath('https://example.com/remote/asset/'.$assetAlias);
        $asset->setSize(0);
        $asset->setIsPublished(true);

        $this->em->persist($asset);
        $this->em->flush();

        $this->assertNotNull($asset->getUuid());
        $slug = $asset->getSlug();

        $expectedUrl = 'https://localhost/asset/'.$slug.$expectedQuery;

        /** @var AssetModel $assetModel */
        $assetModel = self::getContainer()->get(AssetModel::class);
        $this->assertInstanceOf(AssetModel::class, $assetModel);
        $generatedUrl = $assetModel->generateUrl($asset, $absolute, $clickthrough, $stream);

        $this->assertSame($expectedUrl, $generatedUrl);
    }

    /**
     * @return iterable<string, array<int, bool|string|array<string, string>|null>>
     */
    public static function generateUrlDataProvider(): iterable
    {
        $clickThrough        = ['ct' => 'encoded-string'];
        $clickThroughEncoded = urlencode(base64_encode(serialize($clickThrough)));

        yield 'Absolute URL' => [
            'asset-to-download',
            true,
            [],
            null,
            '',
        ];

        yield 'Absolute URL with clickthrough' => [
            'asset-with-ct',
            true,
            ['ct' => 'encoded-string'],
            null,
            '?ct='.$clickThroughEncoded,
        ];

        yield 'Absolute URL with stream' => [
            'stream-asset',
            true,
            [],
            '1',
            '?stream=1',
        ];

        yield 'Absolute URL with stream and clickthrough' => [
            'stream-ct-asset',
            true,
            $clickThrough,
            '0',
            '?stream=0&ct='.$clickThroughEncoded,
        ];
    }

    public function testGenerateUrlWithAliasFallback(): void
    {
        $asset = new Asset();
        $asset->setTitle('asset-alias-fallback');
        $asset->setAlias('the-alias');
        $asset->setDateAdded(new \DateTime());
        $asset->setDateModified(new \DateTime());
        $asset->setCreatedByUser('User');
        $asset->setStorageLocation('remote');
        $asset->setRemotePath('https://example.com/remote/asset/the-alias');
        $asset->setSize(0);
        $asset->setIsPublished(true);

        $this->em->persist($asset);
        $this->em->flush();

        // Set UUID to null in memory to test the fallback.
        $asset->setUuid(null);

        $this->assertNull($asset->getUuid());
        $this->assertSame('1:the-alias', $asset->getSlug());

        /** @var AssetModel $assetModel */
        $assetModel = self::getContainer()->get(AssetModel::class);
        $this->assertInstanceOf(AssetModel::class, $assetModel);
        $generatedUrl = $assetModel->generateUrl($asset, true, []);
        $this->assertSame('https://localhost/asset/1:the-alias', $generatedUrl);
    }

    public function testGetAssetListRespectsCanViewOthersOption(): void
    {
        $currentUserId = self::getContainer()->get(UserHelper::class)->getUser()->getId();
        $dateFrom      = new \DateTime('-1 day', new \DateTimeZone('UTC'));
        $dateTo        = new \DateTime('+1 day', new \DateTimeZone('UTC'));

        $ownAsset = new Asset();
        $ownAsset->setTitle('Own Asset');
        $ownAsset->setAlias('own-asset');
        $ownAsset->setDateAdded(new \DateTime('now', new \DateTimeZone('UTC')));
        $ownAsset->setDateModified(new \DateTime('now', new \DateTimeZone('UTC')));
        $ownAsset->setCreatedBy($currentUserId);
        $ownAsset->setCreatedByUser('Current User');
        $ownAsset->setStorageLocation('remote');
        $ownAsset->setRemotePath('https://example.com/remote/own-asset');
        $ownAsset->setSize(0);
        $ownAsset->setIsPublished(true);

        $foreignAsset = new Asset();
        $foreignAsset->setTitle('Foreign Asset');
        $foreignAsset->setAlias('foreign-asset');
        $foreignAsset->setDateAdded(new \DateTime('now', new \DateTimeZone('UTC')));
        $foreignAsset->setDateModified(new \DateTime('now', new \DateTimeZone('UTC')));
        $foreignAsset->setCreatedBy($currentUserId + 9999);
        $foreignAsset->setCreatedByUser('Foreign User');
        $foreignAsset->setStorageLocation('remote');
        $foreignAsset->setRemotePath('https://example.com/remote/foreign-asset');
        $foreignAsset->setSize(0);
        $foreignAsset->setIsPublished(true);

        $this->em->persist($ownAsset);
        $this->em->persist($foreignAsset);
        $this->em->flush();

        /** @var AssetModel $assetModel */
        $assetModel = self::getContainer()->get(AssetModel::class);
        $this->assertInstanceOf(AssetModel::class, $assetModel);

        $ownOnlyList = $assetModel->getAssetList(10, $dateFrom, $dateTo, [], ['canViewOthers' => false]);
        $allList     = $assetModel->getAssetList(10, $dateFrom, $dateTo, [], ['canViewOthers' => true]);

        $ownOnlyNames = array_column($ownOnlyList, 'name');
        $allNames     = array_column($allList, 'name');

        $this->assertContains('Own Asset', $ownOnlyNames);
        $this->assertNotContains('Foreign Asset', $ownOnlyNames);
        $this->assertContains('Own Asset', $allNames);
        $this->assertContains('Foreign Asset', $allNames);
    }
}
