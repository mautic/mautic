<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UploadControllerFunctionalTest extends MauticMysqlTestCase
{
    private string $assetPath;
    private string $tempId;

    /**
     * @var string[]
     */
    private array $cleanupPaths = [];

    protected function setUp(): void
    {
        $this->configParams['allowed_extensions'] = ['csv', 'gif', 'jpg', 'jpeg', 'png'];

        parent::setUp();
        $this->assetPath      = self::getContainer()->getParameter('mautic.upload_dir');
        $this->tempId         = uniqid('tempId_');
        $this->cleanupPaths[] = $this->assetPath.'/tmp/'.$this->tempId;
    }

    protected function beforeTearDown(): void
    {
        (new Filesystem())->remove($this->cleanupPaths);
    }

    public function testUploadWithWrongClientMimetype(): void
    {
        $filePath = $this->createSourcePath('png');
        $this->copyFile('index.php', $filePath);

        $this->upload($this->createUploadedFile($filePath, 'application/x-httpd-php'));

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('The file content does not match the file extension.', $content);
        $this->assertStringContainsString('The extension of the file is \u0027png\u0027 while the content has mimetype \u0027application\/x-httpd-php\u0027.', $content);
    }

    public function testUploadWithWrongFileMimetype(): void
    {
        $filePath = $this->createSourcePath('png');
        $this->copyFile('index.php', $filePath);

        $this->upload($this->createUploadedFile($filePath, 'image/png'));

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('The file content does not match the file extension.', $content);
        $this->assertStringContainsString('The extension of the file is \u0027png\u0027 while the content has mimetype \u0027text\/x-php\u0027.', $content);
    }

    public function testSuccessUploadWithPng(): void
    {
        $filePath = $this->createSourcePath('png');
        $this->copyFile('app/assets/images/mautic_logo_db64.png', $filePath);

        $this->upload($this->createUploadedFile($filePath, 'image/png'));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringContainsString('state":1', $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string[]>
     */
    public static function dataUploadWithInvalidExtension(): iterable
    {
        foreach (['doc', 'isc', 'php'] as $extension) {
            yield '.'.$extension => [$extension];
        }
    }

    #[DataProvider('dataUploadWithInvalidExtension')]
    public function testUploadWithInvalidExtension(string $extension): void
    {
        $filePath = $this->createSourcePath($extension);
        file_put_contents($filePath, 'dummy content');

        $this->upload($this->createUploadedFile($filePath, null));

        $content    = $this->client->getResponse()->getContent();
        $extensions = implode(', ', $this->configParams['allowed_extensions']);
        $this->assertStringContainsString(sprintf(
            'Upload failed as the file extension, %s, is not in the list of allowed extensions (%s).',
            $extension,
            $extensions
        ), $content);
    }

    private function createSourcePath(string $extension): string
    {
        $path = $this->assetPath.'/'.uniqid('file_').'.'.$extension;

        $this->cleanupPaths[] = $path;

        return $path;
    }

    private function copyFile(string $sourcePath, string $targetPath): void
    {
        copy(self::getContainer()->getParameter('kernel.project_dir').'/'.$sourcePath, $targetPath);
    }

    private function createUploadedFile(string $filePath, ?string $mimeType): UploadedFile
    {
        return new UploadedFile($filePath, basename($filePath), $mimeType, null, true);
    }

    private function upload(UploadedFile $uploadedFile): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/s/_uploader/asset/upload',
            ['tempId' => $this->tempId],
            ['file'   => $uploadedFile]
        );
    }
}
