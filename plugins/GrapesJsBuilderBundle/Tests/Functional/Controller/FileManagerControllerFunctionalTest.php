<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final class FileManagerControllerFunctionalTest extends MauticMysqlTestCase
{
    private const ASSETS_ENDPOINT = '/s/grapesjsbuilder/media';
    private const UPLOAD_ENDPOINT = '/s/grapesjsbuilder/upload';
    private const DELETE_ENDPOINT = '/s/grapesjsbuilder/delete';
    private const IMAGE_COUNT     = 3;
    private const SVG_WIDTH       = 120;
    private const SVG_HEIGHT      = 80;

    /** @var array<string> */
    private array $tempFilePaths = [];

    protected function beforeTearDown(): void
    {
        $this->cleanupTempFiles();
    }

    public function testAssetsManagerWorkflow(): void
    {
        $initialAssetCount = $this->getAssetCount();

        $uploadedFiles = $this->uploadImages();
        $this->assertUploadSuccessful($uploadedFiles);

        $newAssetCount = $this->getAssetCount();
        $this->assertEquals($initialAssetCount + self::IMAGE_COUNT, $newAssetCount);

        $this->testPagination($newAssetCount);
        $this->testRecentlyAddedFilesAppearFirst($uploadedFiles);

        $this->deleteUploadedFiles($uploadedFiles);

        $finalAssetCount = $this->getAssetCount();
        $this->assertEquals($initialAssetCount, $finalAssetCount);
    }

    public function testUploadedSvgIsReturnedInMediaList(): void
    {
        $svgFile  = $this->createTempSvgFile();
        $response = $this->makeRequest('POST', self::UPLOAD_ENDPOINT, [], ['files' => [$svgFile]]);
        $content  = $this->getJsonResponse($response);

        $this->assertArrayHasKey('data', $content);
        $this->assertCount(1, $content['data']);

        $uploadedFiles = $content['data'];
        $uploadedName  = $this->getFileNameFromUrl($uploadedFiles[0]);
        $asset         = $this->findAssetByFileName($uploadedName);

        $this->assertNotNull($asset);
        $this->assertArrayHasKey('type', $asset);
        $this->assertArrayHasKey('width', $asset);
        $this->assertArrayHasKey('height', $asset);
        $this->assertSame('image', $asset['type']);
        $this->assertSame(self::SVG_WIDTH, (int) $asset['width']);
        $this->assertSame(self::SVG_HEIGHT, (int) $asset['height']);

        // Attempt to delete via the API endpoint first.
        $this->deleteUploadedFiles($uploadedFiles);

        // Ensure the uploaded SVG is also removed from the filesystem in case the
        // delete endpoint refuses SVGs (for example, due to exif_imagetype).
        $projectRoot = \dirname(__DIR__, 5);
        $svgPath     = $projectRoot.'/media/images/'.$uploadedName;

        if (\is_file($svgPath)) {
            @\unlink($svgPath);
        }
    }

    private function getAssetCount(): int
    {
        $response = $this->makeRequest('GET', self::ASSETS_ENDPOINT);
        $content  = $this->getJsonResponse($response);

        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('page', $content);
        $this->assertArrayHasKey('limit', $content);
        $this->assertArrayHasKey('totalItems', $content);
        $this->assertArrayHasKey('totalPages', $content);
        $this->assertArrayHasKey('hasNextPage', $content);
        $this->assertArrayHasKey('hasPreviousPage', $content);

        return $content['totalItems'];
    }

    private function testPagination(int $totalAssets): void
    {
        $limit      = 2;
        $totalPages = ceil($totalAssets / $limit);

        for ($page = 1; $page <= $totalPages; ++$page) {
            $response = $this->makeRequest('GET', self::ASSETS_ENDPOINT."?limit={$limit}&page={$page}");
            $content  = $this->getJsonResponse($response);

            $this->assertArrayHasKey('data', $content);
            $this->assertArrayHasKey('page', $content);
            $this->assertArrayHasKey('limit', $content);
            $this->assertArrayHasKey('totalItems', $content);
            $this->assertArrayHasKey('totalPages', $content);
            $this->assertArrayHasKey('hasNextPage', $content);
            $this->assertArrayHasKey('hasPreviousPage', $content);

            $this->assertEquals($page, $content['page']);
            $this->assertEquals($limit, $content['limit']);
            $this->assertEquals($totalAssets, $content['totalItems']);
            $this->assertEquals($totalPages, $content['totalPages']);

            $this->assertEquals($page < $totalPages, $content['hasNextPage']);
            $this->assertEquals($page > 1, $content['hasPreviousPage']);

            $expectedItemCount = ($page < $totalPages) ? $limit : (($totalAssets % $limit) ?: $limit);
            $this->assertCount($expectedItemCount, $content['data']);

            foreach ($content['data'] as $item) {
                $this->assertArrayHasKey('src', $item);
                $this->assertArrayHasKey('width', $item);
                $this->assertArrayHasKey('height', $item);
                $this->assertArrayHasKey('type', $item);
            }
        }

        // Test invalid page
        $response = $this->makeRequest('GET', self::ASSETS_ENDPOINT."?limit={$limit}&page=".($totalPages + 1));
        $content  = $this->getJsonResponse($response);
        $this->assertEmpty($content['data']);
    }

    /**
     * @param array<string> $uploadedFiles
     */
    private function testRecentlyAddedFilesAppearFirst(array $uploadedFiles): void
    {
        $response = $this->makeRequest('GET', self::ASSETS_ENDPOINT);
        $content  = $this->getJsonResponse($response);

        $this->assertArrayHasKey('data', $content);
        $this->assertNotEmpty($content['data']);

        $assetList         = $content['data'];
        $uploadedFileNames = array_map([$this, 'getFileNameFromUrl'], $uploadedFiles);

        // Check if the first 'IMAGE_COUNT' assets in the list are the recently uploaded files
        for ($i = 0; $i < self::IMAGE_COUNT; ++$i) {
            $this->assertArrayHasKey($i, $assetList);
            $this->assertArrayHasKey('src', $assetList[$i]);
            $assetFileName = $this->getFileNameFromUrl($assetList[$i]['src']);
            $this->assertContains($assetFileName, $uploadedFileNames, 'Recently uploaded file not found in the first {self::IMAGE_COUNT} assets');
        }
    }

    /**
     * @return array<string>
     */
    private function uploadImages(): array
    {
        $imageFiles = $this->createTempImageFiles();
        $response   = $this->makeRequest('POST', self::UPLOAD_ENDPOINT, [], ['files' => $imageFiles]);

        return $this->getJsonResponse($response)['data'];
    }

    /**
     * @return array<UploadedFile>
     */
    private function createTempImageFiles(): array
    {
        $imageFiles = [];
        for ($i = 1; $i <= self::IMAGE_COUNT; ++$i) {
            $imagePath = sys_get_temp_dir()."/test-image-{$i}.png";
            $this->createImage($imagePath);
            $this->tempFilePaths[] = $imagePath;
            $imageFiles[]          = new UploadedFile($imagePath, "test-image-{$i}.png", 'image/png', null, true);
        }

        return $imageFiles;
    }

    private function createImage(string $path): void
    {
        $image = imagecreatetruecolor(100, 100);
        imagepng($image, $path);
    }

    private function createTempSvgFile(): UploadedFile
    {
        $fileName = sprintf('test-image-svg-%s.svg', uniqid('', true));
        $filePath = sys_get_temp_dir().'/'.$fileName;
        $this->createSvgImage($filePath);
        $this->tempFilePaths[] = $filePath;

        return new UploadedFile($filePath, $fileName, 'image/svg+xml', null, true);
    }

    private function createSvgImage(string $path): void
    {
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d"><rect width="100%%" height="100%%" fill="#ff6f61"/></svg>',
            self::SVG_WIDTH,
            self::SVG_HEIGHT,
            self::SVG_WIDTH,
            self::SVG_HEIGHT,
        );

        file_put_contents($path, $svg);
    }

    /**
     * @param array<string> $uploadedFiles
     */
    private function assertUploadSuccessful(array $uploadedFiles): void
    {
        $this->assertCount(self::IMAGE_COUNT, $uploadedFiles);
    }

    /**
     * @param array<string> $uploadedFiles
     */
    private function deleteUploadedFiles(array $uploadedFiles): void
    {
        foreach ($uploadedFiles as $uploadedFile) {
            $fileName = $this->getFileNameFromUrl($uploadedFile);
            $this->makeRequest('GET', self::DELETE_ENDPOINT."?filename={$fileName}");
        }
    }

    private function getFileNameFromUrl(string $url): string
    {
        $fileUrlParts = explode('/', $url);

        return end($fileUrlParts);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findAssetByFileName(string $fileName): ?array
    {
        $page  = 1;
        $limit = 50;

        do {
            $response = $this->makeRequest('GET', self::ASSETS_ENDPOINT."?limit={$limit}&page={$page}");
            $content  = $this->getJsonResponse($response);

            foreach ($content['data'] as $asset) {
                if (!isset($asset['src'])) {
                    continue;
                }

                if ($this->getFileNameFromUrl($asset['src']) === $fileName) {
                    return $asset;
                }
            }

            ++$page;
        } while ($content['hasNextPage']);

        return null;
    }

    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $files
     */
    private function makeRequest(string $method, string $endpoint, array $parameters = [], array $files = []): Response
    {
        $this->client->request($method, $endpoint, $parameters, $files);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function getJsonResponse(Response $response): array
    {
        return json_decode($response->getContent(), true);
    }

    private function cleanupTempFiles(): void
    {
        foreach ($this->tempFilePaths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
