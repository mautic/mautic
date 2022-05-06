<?php

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class PublicControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }

    /**
     * Download action should ....
     */
    public function testDownloadAction(): void
    {
        $csvPath = $this->generateCsv();

        $expectedCsvContent         = file_get_contents($csvPath);
        $expectedMimeType           = 'text/plain; charset=UTF-8';
        $expectedContentDisposition = 'attachment;filename="';

        $assetData = [
            'title'     => 'Test',
            'alias'     => 'Test',
            'createdAt' => new \DateTime('2021-05-05 22:30:00'),
            'updatedAt' => new \DateTime('2022-05-05 22:30:00'),
            'createdBy' => 'User',
            'storage'   => 'local',
            'path'      => basename($csvPath),
            'extension' => 'csv',
        ];
        $asset = $this->createAsset($assetData);

        $assetSlug = $asset->getId().':'.$asset->getAlias();

        $this->client->request('GET', '/asset/'.$assetSlug);

        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedMimeType, $response->headers->get('Content-Type'));
        $this->assertNotSame($expectedContentDisposition.$asset->getOriginalFileName(), $response->headers->get('Content-Disposition'));
        $this->assertEquals($expectedCsvContent, $content);

        $this->client->request('GET', '/asset/'.$assetSlug.'?stream=0');

        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedContentDisposition.$asset->getOriginalFileName(), $response->headers->get('Content-Disposition'));
        $this->assertEquals($expectedCsvContent, $content);
    }

    private function createAsset(array $assetData): Asset
    {
        $asset = new Asset();
        $asset->setTitle($assetData['title']);
        $asset->setAlias($assetData['alias']);
        $asset->setDateAdded($assetData['createdAt']);
        $asset->setDateModified($assetData['updatedAt']);
        $asset->setCreatedByUser($assetData['createdBy']);
        $asset->setStorageLocation($assetData['storage']);
        $asset->setPath($assetData['path']);
        $asset->setExtension($assetData['extension']);

        $this->em->persist($asset);
        $this->em->flush();
        $this->em->clear();

        return $asset;
    }

    public static function generateCsv()
    {
        $uploadDir  = self::$container->get('mautic.helper.core_parameters')->get('upload_dir') ?? sys_get_temp_dir();
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

        return $tmpFile;
    }
}
