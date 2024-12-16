<?php

namespace Mautic\AssetBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class AssetApiControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testCreateNewRemoteAsset()
    {
        $payload = [
            'file'            => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            'storageLocation' => 'remote',
            'title'           => 'title',
        ];
        $this->client->request('POST', 'api/assets/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertEquals($payload['title'], $response['asset']['title']);
        $this->assertEquals($payload['storageLocation'], $response['asset']['storageLocation']);
        $this->assertStringContainsString('application/pdf', $response['asset']['mime']);
        $this->assertStringContainsString('pdf', $response['asset']['extension']);
        $this->assertNotNull($response['asset']['size']);
    }

    public function testCreateNewRemoteAssetWithVulnerableFile(): void
    {
        $payload = [
            'file'            => 'file:///etc/passwd',
            'storageLocation' => 'remote',
            'title'           => 'title',
        ];
        $this->client->request('POST', 'api/assets/new', $payload);
        $clientResponse = $this->client->getResponse();
        $this->assertSame(400, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertEquals('{"errors":[{"code":400,"message":"remotePath: The remote should be a valid URL.","details":{"remotePath":["The remote should be a valid URL."]}}]}', $clientResponse->getContent());
    }

    public function testCreateNewLocalAsset()
    {
        $assetsPath = $this->client->getKernel()->getContainer()->getParameter('mautic.upload_dir');
        file_put_contents($assetsPath.'/file.txt', 'test');

        $payload = [
            'file'            => 'file.txt',
            'storageLocation' => 'local',
            'title'           => 'title',
        ];
        $this->client->request('POST', 'api/assets/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertEquals($payload['title'], $response['asset']['title']);
        $this->assertEquals($payload['storageLocation'], $response['asset']['storageLocation']);
        $this->assertStringContainsString('text/plain', $response['asset']['mime']);
        $this->assertNotNull($response['asset']['size']);
        $this->assertStringContainsString('txt', $response['asset']['extension']);
        unlink($assetsPath.'/file.txt');
    }
}
