<?php

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Tests\Asset\AbstractAssetTest;
use Mautic\CoreBundle\Tests\Traits\ControllerTrait;
use Mautic\PageBundle\Tests\Controller\PageControllerTest;
use Symfony\Component\HttpFoundation\Response;

class AssetControllerFunctionalTest extends AbstractAssetTest
{
    use ControllerTrait;

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
     * Preview action should return the file content.
     */
    public function testPreviewActionStreamByDefault(): void
    {
        $this->client->request('GET', '/s/assets/preview/'.$this->asset->getId());
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($this->expectedMimeType, $response->headers->get('Content-Type'));
        $this->assertNotSame($this->expectedContentDisposition.$this->asset->getOriginalFileName(), $response->headers->get('Content-Disposition'));
        $this->assertEquals($this->expectedPngContent, $content);
    }

    /**
     * Preview action should return the file content.
     */
    public function testPreviewActionStreamIsZero(): void
    {
        $this->client->request('GET', '/s/assets/preview/'.$this->asset->getId().'?stream=0&download=1');
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($this->expectedContentDisposition.$this->asset->getOriginalFileName(), $response->headers->get('Content-Disposition'));
        $this->assertEquals($this->expectedPngContent, $content);
    }

    /**
     * Preview action should return the html code.
     */
    public function testPreviewActionStreamDownloadAreZero(): void
    {
        $this->client->request('GET', '/s/assets/preview/'.$this->asset->getId().'?stream=0&download=0');
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertNotEquals($this->expectedPngContent, $content);
        PageControllerTest::assertTrue($response->isOk());

        $assetSlug = $this->asset->getId().':'.$this->asset->getAlias();
        PageControllerTest::assertStringContainsString(
            '/asset/'.$assetSlug,
            $content,
            'The return must contain the assert slug'
        );
    }
}
