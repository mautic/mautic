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

    protected string $csvPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generateCsv();

        $assetData = [
            'title'     => 'Asset controller test. Preview action',
            'alias'     => 'Test',
            'createdAt' => new \DateTime('2021-05-05 22:30:00'),
            'updatedAt' => new \DateTime('2022-05-05 22:30:00'),
            'createdBy' => 'User',
            'storage'   => 'local',
            'path'      => basename($this->csvPath),
            'extension' => 'png',
        ];
        $this->asset = $this->createAsset($assetData);

        $this->expectedMimeType           = 'text/plain; charset=UTF-8';
        $this->expectedContentDisposition = 'attachment;filename="';
        $this->expectedPngContent         = file_get_contents($this->csvPath);
    }

    protected function beforeTearDown(): void
    {
        if (file_exists($this->csvPath)) {
            unlink($this->csvPath);
        }
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
        $asset->setSize($this->csvPath ? filesize($this->csvPath) : 0);

        $this->em->persist($asset);
        $this->em->flush();
        $this->em->detach($asset);

        return $asset;
    }

    /**
     * Generate the csv asset and return the path of the asset.
     */
    protected function generateCsv(): void
    {
        $uploadDir  = static::getContainer()->get('mautic.helper.core_parameters')->get('upload_dir') ?? sys_get_temp_dir();
        $tmpFile    = tempnam($uploadDir, 'mautic_asset_test_');
        $file       = fopen($tmpFile, 'w');

        $initialList = [
            ['email', 'firstname', 'lastname'],
            ['john.doe@his-site.com.email', 'John', 'Doe'],
            ['john.smith@his-site.com.email', 'John', 'Smith'],
            ['jim.doe@his-site.com.email', 'Jim', 'Doe'],
            [''],
            ['jim.smith@his-site.com.email', 'Jim', 'Smith'],
        ];

        foreach ($initialList as $line) {
            fputcsv($file, $line);
        }

        fclose($file);

        $this->csvPath = $tmpFile;
    }
}
