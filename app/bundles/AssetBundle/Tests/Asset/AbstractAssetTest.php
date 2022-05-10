<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Asset;

use Doctrine\ORM\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

abstract class AbstractAssetTest extends MauticMysqlTestCase
{
    /**
     * Create an asset entity in the DB.
     *
     * @param array<string, string|mixed> $assetData
     *
     * @throws ORMException
     * @throws MappingException
     */
    protected function createAsset(array $assetData): Asset
    {
        $asset = new Asset();
        $asset->setTitle($assetData['title']);
        $asset->setAlias($assetData['alias']);
        $asset->setDateAdded($assetData['createdAt'] ?? new \DateTime());
        $asset->setDateModified($assetData['updatedAt'] ?? new \DateTime());
        $asset->setCreatedByUser($assetData['createdBy'] ?? 'User');
        $asset->setStorageLocation($assetData['storage'] ?? 'local');
        $asset->setPath($assetData['path'] ?? '');
        $asset->setExtension($assetData['extension'] ?? '');

        $this->em->persist($asset);
        $this->em->flush();
        $this->em->clear();

        return $asset;
    }

    /**
     * Path to the png test asset.
     */
    protected function getPngFilenameFromFixtures(): string
    {
        return realpath(dirname(__FILE__).'/../../../CoreBundle/Tests/Fixtures/').'/png-test.png';
    }
}
