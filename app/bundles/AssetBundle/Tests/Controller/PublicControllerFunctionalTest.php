<?php

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Download;
use Mautic\AssetBundle\Tests\Asset\AbstractAssetTestCase;
use Symfony\Component\HttpFoundation\Response;

class PublicControllerFunctionalTest extends AbstractAssetTestCase
{
    /**
     * Download action should return the file content.
     */
    public function testDownloadActionStreamByDefault(): void
    {
        $this->client->request('GET', '/asset/'.$this->asset->getSlug());
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertResponseIsSuccessful();
        $this->assertSame($this->expectedMimeType, $response->headers->get('Content-Type'));
        $this->assertNotSame($this->expectedContentDisposition.$this->asset->getOriginalFileName(), $response->headers->get('Content-Disposition'));
        $this->assertEquals($this->expectedPngContent, $content);
    }

    /**
     * Download action should return the file content.
     */
    public function testDownloadActionStreamIsZero(): void
    {
        $this->client->request('GET', '/asset/'.$this->asset->getSlug().'?stream=0');
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertResponseIsSuccessful();
        $this->assertStringStartsWith($this->expectedContentDisposition.$this->asset->getOriginalFileName(), $response->headers->get('Content-Disposition'));
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

        $this->assertResponseIsSuccessful();
        $this->assertStringStartsWith($this->expectedContentDisposition.$this->asset->getOriginalFileName(), $response->headers->get('Content-Disposition'));
        $this->assertEquals($this->expectedPngContent, $content);
    }

    /**
     * Download action with UTM should return the file content.
     */
    public function testDownloadActionWithUTM(): void
    {
        $this->logoutUser();
        $assetSlug = $this->asset->getSlug().'?utm_source=test2&utm_medium=test3&utm_campaign=test6&utm_term=test4&utm_content=test5';

        $this->client->request('GET', '/asset/'.$assetSlug);
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertResponseIsSuccessful();
        $this->assertSame($this->expectedMimeType, $response->headers->get('Content-Type'));
        $this->assertNotSame($this->expectedContentDisposition.$this->asset->getOriginalFileName(), $response->headers->get('Content-Disposition'));
        $this->assertEquals($this->expectedPngContent, $content);

        $downloadRepo = $this->em->getRepository(Download::class);

        $download = $downloadRepo->findOneBy(['asset' => $this->asset]);
        \assert($download instanceof Download);
        $this->assertSame('test2', $download->getUtmSource());
        $this->assertSame('test3', $download->getUtmMedium());
        $this->assertSame('test4', $download->getUtmTerm());
        $this->assertSame('test5', $download->getUtmContent());
        $this->assertSame('test6', $download->getUtmCampaign());
    }

    public function testDownloadActionWithInvalidSlug(): void
    {
        $this->client->request('GET', '/asset/1:invalid-slug-with-special-chars!');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDownloadActionWithUnpublishedAsset(): void
    {
        $this->logoutUser();
        $asset = $this->createAsset(['title' => 'Unpublished Asset', 'isPublished' => false]);
        $this->em->flush();

        $this->client->request('GET', '/asset/'.$asset->getSlug());
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDownloadActionWithRemoteAsset(): void
    {
        $this->logoutUser();

        $remotePath = 'https://example.com/remote-asset.png';
        $asset      = $this->createAsset([
            'title'   => 'Remote Asset',
            'storage' => 'remote',
            'path'    => $remotePath,
        ]);

        $this->em->clear();

        // Don't follow redirects automatically
        $this->client->followRedirects(false);
        $this->client->request('GET', '/asset/'.$asset->getSlug());

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertSame($remotePath, $response->headers->get('Location'));
    }

    public function testDownloadActionWithMissingLocalFile(): void
    {
        $this->logoutUser();
        $asset                = $this->createAsset(['title' => 'Missing Local File Asset']);
        $coreParametersHelper = static::getContainer()->get('mautic.helper.core_parameters');
        $asset->setUploadDir($coreParametersHelper->get('upload_dir'));
        $this->em->flush();

        $assetPath = $asset->getAbsolutePath();

        // Assert the file exists before attempting to delete
        $this->assertFileExists($assetPath, 'Expected asset file to exist before deletion');
        unlink($assetPath);

        $this->client->request('GET', '/asset/'.$this->asset->getSlug());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDownloadActionWithDisallowedAssetSetsRobotsTag(): void
    {
        $this->logoutUser();
        $asset = $this->createAsset(['title' => 'Disallowed Asset']);
        $asset->setDisallow(true);
        $this->em->flush();

        $this->client->request('GET', '/asset/'.$this->asset->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSame('noindex, nofollow, noarchive', $this->client->getResponse()->headers->get('X-Robots-Tag'));
    }
}
