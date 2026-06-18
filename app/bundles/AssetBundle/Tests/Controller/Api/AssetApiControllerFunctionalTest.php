<?php

namespace Mautic\AssetBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AssetApiControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['allowed_extensions']      = ['txt', 'csv', 'jpg', 'jpeg', 'png', 'pdf'];
        $this->configParams['validate_remote_domains'] = false;
        $this->configParams['site_url']                = 'https://raw.githubusercontent.com';

        if ('testCreateNewRemoteAssetWithValidateRemoteDomainsEnabled' === $this->name()) {
            $this->configParams['validate_remote_domains'] = true;
            $this->configParams['allowed_remote_domains']  = [
                'first-allowed.tld',
                'second-allowed.tld',
                'fastly.picsum.photos',
            ];
        }

        parent::setUp();
    }

    public function testCreateNewRemoteAsset(): void
    {
        $payload = [
            'file'            => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            'storageLocation' => 'remote',
            'title'           => 'title',
        ];
        $this->client->request('POST', 'api/assets/new', $payload);
        $clientResponse = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(201, $clientResponse->getContent());
        $response = json_decode($clientResponse->getContent(), true);
        $this->assertEquals($payload['title'], $response['asset']['title']);
        $this->assertEquals($payload['storageLocation'], $response['asset']['storageLocation']);
        $this->assertStringContainsString('application/pdf', $response['asset']['mime']);
        $this->assertStringContainsString('pdf', $response['asset']['extension']);
        $this->assertNotNull($response['asset']['size']);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function dataCreateNewRemoteAssetWithInvalidFile(): iterable
    {
        yield 'Malformed URL' => ['file:///etc/passwd', 'remotePath: The remote should be a valid URL.'];
        yield 'URL returning 404' => ['https://www.google.com/non-existent-path', 'asset: The mimetype of the remote file could not be resolved. Make sure you entered a valid remote URL.'];
        yield 'Not allowed html MIME type' => ['https://github.com/mautic/mautic', 'asset: Upload failed as the file mimetype text\/html'];
        yield 'Not allowed php MIME type' => ['https://raw.githubusercontent.com/mautic/mautic/7.x/index.php', 'asset: Upload failed as the file mimetype text\/x-php'];
    }

    #[DataProvider('dataCreateNewRemoteAssetWithInvalidFile')]
    public function testCreateNewRemoteAssetWithInvalidFile(string $file, string $expectedError): void
    {
        $payload = [
            'file'            => $file,
            'storageLocation' => 'remote',
            'title'           => 'title',
        ];
        $this->client->request('POST', 'api/assets/new', $payload);
        $response = $this->client->getResponse();
        $content  = $response->getContent();
        $this->assertResponseStatusCodeSame(400, $response);
        $this->assertStringContainsString($expectedError, $content);
    }

    /**
     * @return iterable<array{string, bool}>
     */
    public static function dataCreateNewRemoteAssetWithValidateRemoteDomainsEnabled(): iterable
    {
        yield 'Not in allowed domains' => ['https://some-domain.com/foo.jpg', false];
        yield 'Is in allowed domains' => ['https://fastly.picsum.photos/id/13/2500/1667.jpg?hmac=SoX9UoHhN8HyklRA4A3vcCWJMVtiBXUg0W4ljWTor7s', true];
        yield 'Using site URL' => ['https://raw.githubusercontent.com/mautic/mautic/7.x/.github/readme_logo.png', true];
    }

    #[DataProvider('dataCreateNewRemoteAssetWithValidateRemoteDomainsEnabled')]
    public function testCreateNewRemoteAssetWithValidateRemoteDomainsEnabled(string $file, bool $isAllowed): void
    {
        $message = 'remotePath: The remote domain in the URL is not allowed due to security reasons.';
        $payload = [
            'file'            => $file,
            'storageLocation' => 'remote',
            'title'           => 'title',
        ];
        $this->client->request('POST', 'api/assets/new', $payload);
        $clientResponse = $this->client->getResponse();
        $content        = $clientResponse->getContent();

        if ($isAllowed) {
            $this->assertResponseStatusCodeSame(201, $content);
            $this->assertStringNotContainsString($message, $content);
        } else {
            $this->assertResponseStatusCodeSame(400, $content);
            $this->assertStringContainsString($message, $content);
        }
    }

    public function testCreateNewLocalAsset(): void
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
        $this->assertResponseStatusCodeSame(201, $clientResponse->getContent());
        $response = json_decode($clientResponse->getContent(), true);
        $this->assertEquals($payload['title'], $response['asset']['title']);
        $this->assertEquals($payload['storageLocation'], $response['asset']['storageLocation']);
        $this->assertStringContainsString('text/plain', $response['asset']['mime']);
        $this->assertNotNull($response['asset']['size']);
        $this->assertStringContainsString('txt', $response['asset']['extension']);
        unlink($assetsPath.'/file.txt');
    }

    public function testDeleteAssetReturnsSuccessAndEntityIsRemoved(): void
    {
        $payload = [
            'file'            => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
            'storageLocation' => 'remote',
            'title'           => 'asset for delete regression test',
        ];

        // Create asset first
        $this->client->request('POST', 'api/assets/new', $payload);
        $createResponse = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(201, $createResponse->getContent());

        $createdAsset = json_decode($createResponse->getContent(), true);
        $assetId      = $createdAsset['asset']['id'];
        $this->assertNotEmpty($assetId);

        // Delete must not fail with 500 due to post-delete serialization
        $this->client->request('DELETE', sprintf('/api/assets/%d/delete', $assetId));
        $deleteResponse = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(200, $deleteResponse->getContent());

        // Verify the asset is actually removed
        $this->client->request('GET', sprintf('/api/assets/%d', $assetId));
        $getDeletedResponse = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(404, $getDeletedResponse->getContent());
    }
}
