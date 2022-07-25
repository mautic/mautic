<?php

namespace Mautic\AssetBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\AssetBundle\Entity\Asset;

class LoadAssetData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $asset = new Asset();
        $asset
            ->setTitle('@TOCHANGE: Asset1 Title')
            ->setAlias('asset1')
            ->setOriginalFileName('@TOCHANGE: Asset1 Original File Name')
            ->setPath('fdb8e28357b02d12d068de3e5661832e21bc08ec.doc')
            ->setDownloadCount(1)
            ->setUniqueDownloadCount(1)
            ->setRevision(1)
            ->setLanguage('en');

        $manager->persist($asset);
        $manager->flush();
    }

    public function getOrder(): int
    {
        return 10;
    }
}
