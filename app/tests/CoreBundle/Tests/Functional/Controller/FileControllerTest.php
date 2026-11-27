<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class FileControllerTest extends MauticMysqlTestCase
{
    private ?string $uploadedFilePath = null;

    public function testImageUploadSuccess(): void
    {
        $image = $this->createUploadFile('png-test.png', 'tmp-png-test.png');
        $this->client->request(Request::METHOD_POST, 's/file/upload?editor=ckeditor', [], ['upload' => $image]);
        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(true, $responseData['uploaded']);
        $this->arrayHasKey('url');
        $this->assertNotEmpty($responseData['url']);
        $uploadedFileName = basename($responseData['url']);
        $uploadedImage    = self::getContainer()->getParameter('mautic.application_dir').'/media/images/'.$uploadedFileName;
        $this->assertFileExists($uploadedImage);
    }

    public function testImageUploadFailure(): void
    {
        $image = $this->createUploadFile('test.json', 'tmp-test.json');

        $this->client->request(Request::METHOD_POST, 's/file/upload?editor=ckeditor', [], ['upload' => $image]);
        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(false, $responseData['uploaded']);
        $this->assertEquals('The uploaded image does not have an allowed mime type', $responseData['error']['message']);
    }

    private function createUploadFile(string $fileName, string $tmpFile): UploadedFile
    {
        $filePath = $this->getFixurePath();
        copy($filePath.$fileName, $filePath.$tmpFile);
        $this->uploadedFilePath = $filePath.$tmpFile;

        return new UploadedFile(
            $filePath.$tmpFile,
            $tmpFile,
            'image/png'
        );
    }

    private function getFixurePath(): string
    {
        return realpath(__DIR__.'/../../Fixtures/').'/';
    }

    protected function beforeTearDown(): void
    {
        if ($this->uploadedFilePath && file_exists($this->uploadedFilePath)) {
            unlink($this->uploadedFilePath);
        }
    }
}
