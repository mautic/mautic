<?php

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\Download;
use Mautic\AssetBundle\Tests\Asset\AbstractAssetTest;
use Symfony\Component\HttpFoundation\Response;

class PublicControllerFunctionalTest extends AbstractAssetTest
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
        $assetSlug = $this->asset->getId().':'.$this->asset->getAlias();

        $this->client->request('GET', '/asset/'.$assetSlug);
        ob_start();
        $response = $this->client->getResponse();
        $response->sendContent();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame($this->expectedMimeType, $response->headers->get('Content-Type'));
        $this->assertSame('inline; filename="Product brochure.png"', $response->headers->get('Content-Disposition'));
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
        $this->assertSame('attachment; filename="Product brochure.png"', $response->headers->get('Content-Disposition'));
        $this->assertEquals($this->expectedPngContent, $content);
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
        $this->assertSame('inline; filename="Product brochure.png"', $response->headers->get('Content-Disposition'));
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

    /**
     * @dataProvider filenameProvider
     */
    public function testDownloadFilename(string $filename, string $expectedParameters, string $stream, string $disposition): void
    {
        $this->asset->setOriginalFileName($filename);
        $this->em->persist($this->asset);
        $this->em->flush();

        $this->client->request('GET', '/asset/'.$this->asset->getId().':'.$this->asset->getAlias().'?stream='.$stream);

        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
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
}
