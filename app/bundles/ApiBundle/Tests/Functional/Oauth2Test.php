<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Functional;

use Mautic\CoreBundle\Test\IsolatedTestTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

/**
 * This test must run in a separate process because it sets the global constant
 * MAUTIC_INSTALLER which breaks other tests.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class Oauth2Test extends MauticMysqlTestCase
{
    use IsolatedTestTrait;

    protected function setUp(): void
    {
        $this->useCleanupRollback = false;
        $this->useMockServices    = false;

        parent::setUp();
    }

    public function testAuthWithInvalidCredentials(): void
    {
        $this->client->enableReboot();

        // Disable the default logging in via username and password.
        $this->clientServer = [];
        $this->setUpSymfony($this->configParams);

        $this->client->request(
            Request::METHOD_POST,
            '/oauth/v2/token',
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => 'unicorn',
                'client_secret' => 'secretUnicorn',
            ]
        );

        $response = $this->client->getResponse();
        Assert::assertSame(400, $response->getStatusCode(), $response->getContent());
        Assert::assertSame(
            '{"errors":[{"message":"The client credentials are invalid","code":400,"type":"invalid_client"}]}',
            $response->getContent()
        );
    }

    public function testAuthWithInvalidAccessToken(): void
    {
        $this->client->enableReboot();

        // Disable the default logging in via username and password.
        $this->clientServer = [];
        $this->setUpSymfony($this->configParams);

        $this->client->request(
            Request::METHOD_GET,
            '/api/users',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer unicorn_token',
            ],
        );

        $response = $this->client->getResponse();
        Assert::assertSame(401, $response->getStatusCode(), $response->getContent());
        Assert::assertSame('{"errors":[{"message":"The access token provided is invalid.","code":401,"type":"invalid_grant"}]}', $response->getContent());
    }

    public function testAuthWorkflow(): void
    {
        $this->client->enableReboot();

        // Create OAuth2 credentials.
        $crawler    = $this->client->request(Request::METHOD_GET, 's/credentials/new');
        $saveButton = $crawler->selectButton('Save');
        $form       = $saveButton->form();
        $form['client[name]']->setValue('Auth Test');
        $form['client[redirectUris]']->setValue('https://test.org');

        $crawler = $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        $clientPublicKey = $crawler->filter('input#client_publicId')->attr('value');
        $clientSecretKey = $crawler->filter('input#client_secret')->attr('value');

        // Disable the default logging in via username and password.
        $this->clientServer = [];
        $this->setUpSymfony($this->configParams);

        // Get the access token.
        $this->client->request(
            Request::METHOD_POST,
            '/oauth/v2/token',
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientPublicKey,
                'client_secret' => $clientSecretKey,
            ],
        );

        $response = $this->client->getResponse();
        Assert::assertSame(200, $response->getStatusCode(), $response->getContent());
        $payload     = json_decode($response->getContent(), true);
        $accessToken = $payload['access_token'];
        Assert::assertNotEmpty($accessToken);

        // Test that the access token works by fetching users via API.
        $this->client->request(
            Request::METHOD_GET,
            '/api/users',
            [],
            [],
            [
                'HTTP_Authorization' => "Bearer {$accessToken}",
            ],
        );

        $response = $this->client->getResponse();
        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertStringContainsString('"users":[', $response->getContent());
    }
}
