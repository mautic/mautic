<?php

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Download;
use Mautic\AssetBundle\Tests\Asset\AbstractAssetTest;
use Symfony\Component\HttpFoundation\Response;

class PublicControllerFunctionalTest extends AbstractAssetTest
{
    /**
     * Download action should return the file content.
     */
    public function testDownloadActionStreamByDefault(): void
    {
        $assetSlug = $this->asset->getId().':'.$this->asset->getAlias();

        $this->client->request('GET', '/asset/'.$assetSlug);
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
     * Download action should return the file content.
     */
    public function testDownloadActionStreamIsZero(): void
    {
        $assetSlug = $this->asset->getId().':'.$this->asset->getAlias();

        $this->client->request('GET', '/asset/'.$assetSlug.'?stream=0');
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
     * Download action should return the file content.
     */
    public function testDownloadActionById(): void
    {
        $assetSlug = $this->asset->getId().':';

        $this->client->request('GET', '/asset/'.$assetSlug.'?stream=0');
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertStringContainsString('404 Not Found', $content);
    }

    /**
     * Download action with UTM should return the file content.
     */
    public function testDownloadActionWithUTM(): void
    {
        $assetSlug = $this->asset->getId().':'.$this->asset->getAlias().'?utm_source=test2&utm_medium=test3&utm_campaign=test6&utm_term=test4&utm_content=test5';

        $this->client->request('GET', '/asset/'.$assetSlug);
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($this->expectedMimeType, $response->headers->get('Content-Type'));
        $this->assertNotSame($this->expectedContentDisposition.$this->asset->getOriginalFileName(), $response->headers->get('Content-Disposition'));
        $this->assertEquals($this->expectedPngContent, $content);

        $downloadRepo = $this->em->getRepository(Download::class);

        /**
         * @var Download $download
         */
        $download = $downloadRepo->findOneBy(['asset' => $this->asset]);
        $this->assertSame('test2', $download->getUtmSource());
        $this->assertSame('test3', $download->getUtmMedium());
        $this->assertSame('test4', $download->getUtmTerm());
        $this->assertSame('test5', $download->getUtmContent());
        $this->assertSame('test6', $download->getUtmCampaign());
    }
}
