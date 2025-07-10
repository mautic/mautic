<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileControllerTest extends MauticMysqlTestCase
{
    private $uploadedFilePath;

    public function testImageUploadSuccess(): void
    {
        $image = $this->createUploadFile('png-test.png', 'tmp-png-test.png');
        $this->client->request('POST', 's/file/upload?editor=ckeditor', [], ['upload' => $image]);
        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        Assert::assertEquals(true, $responseData['uploaded']);
        Assert::arrayHasKey('url');
        Assert::assertNotEmpty($responseData['url']);
        $uploadedFileName = basename($responseData['url']);
        $uploadedImage    = static::getContainer()->getParameter('mautic.application_dir').'/media/images/'.$uploadedFileName;
        Assert::assertTrue(file_exists($uploadedImage));
    }

    public function testImageUploadFailure(): void
    {
        $image = $this->createUploadFile('test.json', 'tmp-test.json');

        $this->client->request('POST', 's/file/upload?editor=ckeditor', [], ['upload' => $image]);
        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);
        Assert::assertEquals(false, $responseData['uploaded']);
        Assert::assertEquals('The uploaded image does not have an allowed mime type', $responseData['error']['message']);
    }

    private function createUploadFile(string $fileName, string $tmpFile): UploadedFile
    {
        $filePath = $this->getFixurePath();
        copy($filePath.$fileName, $filePath.$tmpFile);
        $this->uploadedFilePath = $filePath.$tmpFile;
        $image                  = new UploadedFile(
            $filePath.$tmpFile,
            $tmpFile,
            'image/png'
        );

        return $image;
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
