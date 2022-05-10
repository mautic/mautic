<?php

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Tests\Asset\AbstractAssetTest;

class PublicControllerFunctionalTest extends AbstractAssetTest
{
    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }

    /**
     * Download action should return the file content.
     */
    public function testDownloadAction(): void
    {
        $expectedMimeType           = 'image/png';
        $expectedContentDisposition = 'attachment;filename="';
        $expectedPngContent         = file_get_contents($this->getPngFilenameFromFixtures());

        $assetData = [
            'title'     => 'Public controller test. Download action',
            'alias'     => 'Test',
            'createdAt' => new \DateTime('2021-05-05 22:30:00'),
            'updatedAt' => new \DateTime('2022-05-05 22:30:00'),
            'createdBy' => 'User',
            'storage'   => 'local',
            'path'      => basename($this->getPngFilenameFromFixtures()),
            'extension' => 'png',
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
        $this->assertEquals($expectedPngContent, $content);

        $this->client->request('GET', '/asset/'.$assetSlug.'?stream=0');
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedContentDisposition.$asset->getOriginalFileName(), $response->headers->get('Content-Disposition'));
        $this->assertEquals($expectedPngContent, $content);
    }
}
