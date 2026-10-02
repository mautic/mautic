<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\AssetBundle\Tests\Asset\AbstractAssetTestCase;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

final class PublicControllerFunctionalTest extends AbstractAssetTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->asset = $this->em->find(Asset::class, $this->asset->getId());
        $this->asset->setOriginalFileName('Product brochure.png');
        $this->em->persist($this->asset);
        $this->em->flush();
    }

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
        $this->assertSame('inline; filename="Product brochure.png"', $response->headers->get('Content-Disposition'));
        $this->assertSame($this->expectedPngContent, $content);
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
        $this->assertSame('attachment; filename="Product brochure.png"', $response->headers->get('Content-Disposition'));
        $this->assertSame($this->expectedPngContent, $content);
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
        $this->assertSame('attachment; filename="Product brochure.png"', $response->headers->get('Content-Disposition'));
        $this->assertSame($this->expectedPngContent, $content);
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
        $this->assertSame('inline; filename="Product brochure.png"', $response->headers->get('Content-Disposition'));
        $this->assertSame($this->expectedPngContent, $content);

        $downloadRepo = $this->em->getRepository(Download::class);

        $download = $downloadRepo->findOneBy(['asset' => $this->asset]);
        $this->assertInstanceOf(Download::class, $download);
        $this->assertSame('test2', $download->getUtmSource());
        $this->assertSame('test3', $download->getUtmMedium());
        $this->assertSame('test4', $download->getUtmTerm());
        $this->assertSame('test5', $download->getUtmContent());
        $this->assertSame('test6', $download->getUtmCampaign());
    }

    #[DataProvider('filenameProvider')]
    public function testDownloadFilename(string $filename, string $expectedParameters, string $stream, string $disposition): void
    {
        $this->asset->setOriginalFileName($filename);
        $this->em->persist($this->asset);
        $this->em->flush();

        $this->client->request('GET', '/asset/'.$this->asset->getSlug().'?stream='.$stream);

        $this->assertResponseIsSuccessful();
        $this->assertSame($disposition.'; '.$expectedParameters, $this->client->getResponse()->headers->get('Content-Disposition'));
        $this->assertSame($this->expectedPngContent, $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function filenameProvider(): iterable
    {
        $filenames = [
            'quotes'          => ['Product "Pro".png', 'filename="Product \\"Pro\\".png"'],
            'unicode'         => ['Broschüre.png', 'filename=Brosch_re.png; filename*=utf-8\'\'Brosch%C3%BCre.png'],
            'percent'         => ['100%.png', 'filename=100_.png; filename*=utf-8\'\'100%25.png'],
            'path separators' => ['Product\\Pro/image.png', 'filename=Product_Pro_image.png'],
        ];

        foreach (['inline' => '1', 'attachment' => '0'] as $disposition => $stream) {
            foreach ($filenames as $name => [$filename, $expectedParameters]) {
                yield $disposition.' '.$name => [$filename, $expectedParameters, $stream, $disposition];
            }
        }
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
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertResponseRedirects($remotePath);
    }

    public function testDownloadActionWithMissingLocalFile(): void
    {
        $this->logoutUser();
        $asset                = $this->createAsset(['title' => 'Missing Local File Asset']);
        /** @var CoreParametersHelper $coreParametersHelper */
        $coreParametersHelper = self::getContainer()->get(CoreParametersHelper::class);
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
