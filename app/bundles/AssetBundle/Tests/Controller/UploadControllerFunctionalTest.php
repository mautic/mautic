<?php

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Tests\Asset\AbstractAssetTest;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UploadControllerFunctionalTest extends AbstractAssetTest
{
    public function testUploadWithWrongMimetype(): void
    {
        // Create a php file with the content of phpinfo
        $assetsPath = $this->client->getKernel()->getContainer()->getParameter('mautic.upload_dir');

        $fileName = 'image2.png';
        $filePath = $assetsPath.'/'.$fileName;

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        copy('index.php', $filePath);

        $binaryFile = new UploadedFile($filePath, $fileName, 'application/x-httpd-php', null, true);

        $tmpId = 'tempId_'.time();
        // Upload the file
        $this->client->request(
            Request::METHOD_POST,
            '/s/_uploader/asset/upload',
            [
                'tempId' => $tmpId,
            ],
            [
                'file' => $binaryFile,
            ]
        );

        $response = $this->client->getResponse();
        $this->assertStringContainsString('Upload failed as the file mimetype', $response->getContent());
        $this->assertStringContainsString('text\/x-php is not allowed', $response->getContent());
        unlink($filePath);
    }

    public function testSuccessUploadWithPng(): void
    {
        // Create a temporary PNG file
        // Create a php file with the content of phpinfo
        $assetsPath     = $this->client->getKernel()->getContainer()->getParameter('mautic.upload_dir');
        $assetsPathFrom = $this->client->getKernel()->getContainer()->getParameter('mautic.application_dir').'/app/assets/images/mautic_logo_db64.png';

        $fileName = 'image3.png';
        $filePath = $assetsPath.'/'.$fileName;

        copy($assetsPathFrom, $filePath);
        // Create an UploadedFile instance with the correct MIME type
        $uploadedFile = new UploadedFile($filePath, $fileName, 'image/png', null, true);

        $tmpId = 'tempId_'.time();
        // Perform the request with the file
        $this->client->request(
            'POST',
            '/s/_uploader/asset/upload',
            ['tempId' => $tmpId],
            ['file'   => $uploadedFile]
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringContainsString('state":1', $this->client->getResponse()->getContent());
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $data = json_decode($this->client->getResponse()->getContent(), true);
        unlink($assetsPath.'/tmp/'.$tmpId.'/'.$data['tmpFileName']);
        rmdir($assetsPath.'/tmp/'.$tmpId);
    }

    public function testUploadWithWrongExtension(): void
    {
        // Create a php file with the content of phpinfo
        $assetsPath     = $this->client->getKernel()->getContainer()->getParameter('mautic.upload_dir');
        $assetsPathFrom = $this->client->getKernel()->getContainer()->getParameter('mautic.application_dir').'/app/assets/images/mautic_logo_db64.png';

        $fileName = 'image2.php';
        $filePath = $assetsPath.'/'.$fileName;

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        copy($assetsPathFrom, $filePath);

        $binaryFile = new UploadedFile($filePath, $fileName, 'image/png', null, true);

        $tmpId = 'tempId_'.time();
        // Upload the file
        $this->client->request(
            Request::METHOD_POST,
            '/s/_uploader/asset/upload',
            [
                'tempId' => $tmpId,
            ],
            [
                'file' => $binaryFile,
            ]
        );

        $response = $this->client->getResponse();
        $this->assertStringContainsString('Upload failed as the file extension', $response->getContent());
        $this->assertStringContainsString('Upload failed as the file extension, php,', $response->getContent());
        unlink($filePath);
    }
}
