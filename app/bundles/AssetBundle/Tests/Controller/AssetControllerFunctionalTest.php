<?php

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Tests\Asset\AbstractAssetTest;
use Mautic\CoreBundle\Tests\Traits\ControllerTrait;
use Mautic\PageBundle\Tests\Controller\PageControllerTest;

class AssetControllerFunctionalTest extends AbstractAssetTest
{
    use ControllerTrait;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }

    /**
     * Index action should return status code 200.
     */
    public function testIndexAction(): void
    {
        $assetData = [
            'title'     => 'Asset controller test. Index action',
            'alias'     => 'Test',
            'createdAt' => new \DateTime('2020-02-07 20:29:02'),
            'updatedAt' => new \DateTime('2020-03-21 20:29:02'),
            'createdBy' => 'Test User',
        ];
        $this->createAsset($assetData);

        $urlAlias   = 'assets';
        $routeAlias = 'asset';
        $column     = 'dateModified';
        $column2    = 'title';
        $tableAlias = 'a.';

        $this->getControllerColumnTests($urlAlias, $routeAlias, $column, $tableAlias, $column2);
    }

    /**
     * Preview action should return the file content or the html code.
     */
    public function testPreviewAction(): void
    {
        $expectedMimeType           = 'image/png';
        $expectedContentDisposition = 'attachment;filename="';
        $expectedPngContent         = file_get_contents($this->getPngFilenameFromFixtures());

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
        $asset = $this->createAsset($assetData);

        $this->client->request('GET', '/s/assets/preview/'.$asset->getId());
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedMimeType, $response->headers->get('Content-Type'));
        $this->assertNotSame($expectedContentDisposition.$asset->getOriginalFileName(), $response->headers->get('Content-Disposition'));
        $this->assertEquals($expectedPngContent, $content);

        $this->client->request('GET', '/s/assets/preview/'.$asset->getId().'?stream=0&download=1');
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedContentDisposition.$asset->getOriginalFileName(), $response->headers->get('Content-Disposition'));
        $this->assertEquals($expectedPngContent, $content);

        $this->client->request('GET', '/s/assets/preview/'.$asset->getId().'?stream=0&download=0');
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotEquals($expectedPngContent, $content);
        PageControllerTest::assertTrue($response->isOk());

        $url       = self::$container->get('mautic.helper.core_parameters')->get('site_url');
        $assetSlug = $asset->getId().':'.$asset->getAlias();
        PageControllerTest::assertStringContainsString(
            'img src="'.$url.'/asset/'.$assetSlug,
            $content,
            'The return must contain the assert slug'
        );
    }
}
