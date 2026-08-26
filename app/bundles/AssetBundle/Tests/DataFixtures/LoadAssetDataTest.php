<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\DataFixtures;

use Mautic\AssetBundle\DataFixtures\ORM\LoadAssetData;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

final class LoadAssetDataTest extends MauticMysqlTestCase
{
    public function testLoadFixtures(): void
    {
        $this->loadFixtures([LoadAssetData::class]);
        $asset = $this->em->getRepository(Asset::class)->findOneBy(
            ['title' => '@TOCHANGE: Asset1 Title'],
            ['id' => 'DESC']
        );
        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertSame('asset1', $asset->getAlias());
        $this->assertSame('@TOCHANGE: Asset1 Original File Name', $asset->getOriginalFileName());
        $this->assertSame('fdb8e28357b02d12d068de3e5661832e21bc08ec.doc', $asset->getPath());
        $this->assertSame(1, $asset->getDownloadCount());
        $this->assertSame(1, $asset->getUniqueDownloadCount());
        $this->assertSame(1, $asset->getRevision());
        $this->assertSame('en', $asset->getLanguage());
    }

    public function testLoadFixturesOrder(): void
    {
        $loadAssetData = new LoadAssetData();
        $this->assertSame(10, $loadAssetData->getOrder());
    }
}
