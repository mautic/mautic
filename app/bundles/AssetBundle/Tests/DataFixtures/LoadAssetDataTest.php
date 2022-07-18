<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\DataFixtures;

use Mautic\AssetBundle\DataFixtures\ORM\LoadAssetData;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class LoadAssetDataTest extends MauticMysqlTestCase
{
    public function testLoadFixtures(): void
    {
        $this->loadFixtures([LoadAssetData::class]);
        $asset = $this->em->getRepository(Asset::class)->findOneBy(
            ['title' => '@TOCHANGE: Asset1 Title'],
            ['id' => 'DESC']
        );
        self::assertInstanceOf(Asset::class, $asset);
        self::assertEquals('asset1', $asset->getAlias());
        self::assertEquals('@TOCHANGE: Asset1 Original File Name', $asset->getOriginalFileName());
        self::assertEquals('fdb8e28357b02d12d068de3e5661832e21bc08ec.doc', $asset->getPath());
        self::assertEquals(1, $asset->getDownloadCount());
        self::assertEquals(1, $asset->getUniqueDownloadCount());
        self::assertEquals(1, $asset->getRevision());
        self::assertEquals('en', $asset->getLanguage());
    }

    public function testLoadFixturesOrder(): void
    {
        $loadAssetData = new LoadAssetData();
        self::assertEquals(10, $loadAssetData->getOrder());
    }
}
