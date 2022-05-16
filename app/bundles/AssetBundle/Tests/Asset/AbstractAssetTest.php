<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Asset;

use Doctrine\ORM\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

abstract class AbstractAssetTest extends MauticMysqlTestCase
{
    protected Asset $asset;
    protected string $expectedMimeType;
    protected string $expectedContentDisposition;
    protected string $expectedPngContent;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $assetData = [
            'title'     => 'Asset controller test. Preview action',
            'alias'     => 'Test',
            'createdAt' => new \DateTime('2021-05-05 22:30:00'),
            'updatedAt' => new \DateTime('2022-05-05 22:30:00'),
            'createdBy' => 'User',
            'storage'   => 'local',
            'path'      => basename($this->getPngFilenameFromFixtures()),
            'extension' => 'png',
        ];
        $this->asset = $this->createAsset($assetData);

        $this->expectedMimeType           = 'image/png';
        $this->expectedContentDisposition = 'attachment;filename="';
        $this->expectedPngContent         = file_get_contents($this->getPngFilenameFromFixtures());
    }

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
