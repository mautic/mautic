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
        imagedestroy($image);
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
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $files
     */
    private function makeRequest(string $method, string $endpoint, array $parameters = [], array $files = []): Response
    {
        $this->client->request($method, $endpoint, $parameters, $files);
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

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
