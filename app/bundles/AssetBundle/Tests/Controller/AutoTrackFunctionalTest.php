<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Response;

class AutoTrackFunctionalTest extends MauticMysqlTestCase
{
    protected array $configParams = [
        'api_enabled'                       => true,
        'api_enable_basic_auth'             => true,
        'create_custom_field_in_background' => false,
        'site_url'                          => 'https://localhost',
        'mailer_dsn'                        => 'null://null',
        'messenger_dsn_email'               => 'in-memory://default',
        'messenger_dsn_hit'                 => 'sync://',
        'messenger_dsn_failed'              => 'in-memory://default',
        'auto_asset_tracking_enabled'       => true,
        'track_private_ip_ranges'           => true,
    ];

    public function testSkipsAuthenticatedUsers(): void
    {
        // Default client is authenticated
        $this->client->request('POST', '/mtc/asset/track', [
            'url' => 'https://example.com/test.pdf',
        ]);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Authenticated users are not tracked', $response['reason']);
    }

    public function testRejectsInvalidUrl(): void
    {
        $this->logoutUser();
        $this->client->request('POST', '/mtc/asset/track', [
            'url' => 'not-a-url',
        ], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0']);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Invalid URL', $response['error']);
    }

    public function testRejectsPrivateUrl(): void
    {
        $this->logoutUser();
        $this->client->request('POST', '/mtc/asset/track', [
            'url' => 'https://192.168.1.1/secret.pdf',
        ], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0']);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testSkipsNonTrackableExtension(): void
    {
        $this->logoutUser();
        $this->client->request('POST', '/mtc/asset/track', [
            'url' => 'https://example.com/page.html',
        ], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0']);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Not a trackable file type', $response['reason']);
    }

    public function testCreatesRemoteAssetWithCorrectTitleAndAlias(): void
    {
        $this->logoutUser();
        $this->client->request('POST', '/mtc/asset/track', [
            'url' => 'https://example.com/annual-report.pdf',
        ], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0']);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertNotEmpty($response['assetId']);

        $asset = $this->em->getRepository(Asset::class)->find($response['assetId']);
        $this->assertSame('annual-report.pdf', $asset->getTitle());
        $this->assertSame('annual-report', $asset->getAlias());
        $this->assertSame('remote', $asset->getStorageLocation());
        $this->assertSame('https://example.com/annual-report.pdf', $asset->getRemotePath());
        $this->assertTrue($asset->isPublished());
    }

    public function testCreatesAssetWithCustomTitle(): void
    {
        $this->logoutUser();
        $this->client->request('POST', '/mtc/asset/track', [
            'url'   => 'https://example.com/doc123.pdf',
            'title' => 'My Custom Report',
        ], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0']);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);

        $asset = $this->em->getRepository(Asset::class)->find($response['assetId']);
        $this->assertSame('My Custom Report', $asset->getTitle());
    }

    public function testReturnExistingAssetForDuplicateUrl(): void
    {
        $this->logoutUser();

        // First request creates the asset
        $this->client->request('POST', '/mtc/asset/track', [
            'url' => 'https://example.com/duplicate-test.pdf',
        ], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0']);
        $first = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($first['success']);
        $this->assertArrayNotHasKey('existing', $first);

        // Second request returns existing
        $this->client->request('POST', '/mtc/asset/track', [
            'url' => 'https://example.com/duplicate-test.pdf',
        ], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0']);
        $second = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($second['success']);
        $this->assertTrue($second['existing']);
        $this->assertSame($first['assetId'], $second['assetId']);
    }

    public function testSkipsLocalMauticAssetUrl(): void
    {
        $this->logoutUser();
        $this->client->request('POST', '/mtc/asset/track', [
            'url' => 'https://example.com/asset/123:my-asset.pdf',
        ], [], ['HTTP_USER_AGENT' => 'Mozilla/5.0']);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Already a local Mautic asset', $response['reason']);
    }
}
