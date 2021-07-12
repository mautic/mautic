<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class FileControllerTest extends MauticMysqlTestCase
{
    private $uploadedFilePath;

    public function testImageUploadSuccess(): void
    {
        $image = $this->createUploadFile('png-test.png', 'tmp-png-test.png');
        $this->client->request('POST', 's/file/upload?editor=ckeditor', [], ['upload' => $image]);
        $response = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        Assert::assertEquals(true, $responseData['uploaded']);
        Assert::arrayHasKey('url');
        Assert::assertNotEmpty($responseData['url']);
        $uploadedFileName = basename($responseData['url']);
        $uploadedImage    = static::$container->getParameter('kernel.project_dir').'/media/images/'.$uploadedFileName;
        Assert::assertTrue(file_exists($uploadedImage));
    }

    public function testImageUploadFailure(): void
    {
        $image = $this->createUploadFile('test.json', 'tmp-test.json');

        $this->client->request('POST', 's/file/upload?editor=ckeditor', [], ['upload' => $image]);
        $response = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode());
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
        return realpath(dirname(__FILE__).'/../../Fixtures/').'/';
    }

    protected function beforeTearDown(): void
    {
        if ($this->uploadedFilePath && file_exists($this->uploadedFilePath)) {
            unlink($this->uploadedFilePath);
        }
    }
}
