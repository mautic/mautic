<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Functional;

use Mautic\CoreBundle\Test\IsolatedTestTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This test must run in a separate process because it sets the global constant
 * MAUTIC_INSTALLER which breaks other tests.
 *
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
final class Oauth2Test extends MauticMysqlTestCase
{
    use IsolatedTestTrait;

    protected function setUp(): void
    {
        $this->useCleanupRollback = false;

        parent::setUp();
    }

    /**
     * @dataProvider provideMethods
     */
    public function testAuthorize(string $method): void
    {
        // Disable the default logging in via username and password.
        $this->clientServer = [];
        $this->setUpSymfony($this->configParams);
        $this->client->followRedirects(false);

        $this->client->request(
            $method,
            '/oauth/v2/authorize'
        );

        $this->client->followRedirects(true);

        $response = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_FOUND, $response->getStatusCode(), $response->getContent());
        Assert::assertSame('https://localhost/oauth/v2/authorize_login', $response->headers->get('Location'));
    }

    public static function provideMethods(): \Generator
    {
        yield 'GET' => [Request::METHOD_GET];
        yield 'POST' => [Request::METHOD_POST];
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
        self::assertResponseStatusCodeSame(400, $response->getContent());
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
        self::assertResponseStatusCodeSame(401, $response->getContent());
        Assert::assertSame('{"errors":[{"message":"The access token provided is invalid.","code":401,"type":"invalid_grant"}]}', $response->getContent());
    }

    public function testAuthWorkflow(): void
    {
        $this->client->disableReboot();

        // Create OAuth2 credentials.
        $crawler    = $this->client->request(Request::METHOD_GET, 's/credentials/new');
        $saveButton = $crawler->selectButton('Save');
        $form       = $saveButton->form();
        $form['client[name]']->setValue('Auth Test');
        $form['client[redirectUris]']->setValue('https://test.org');

        $crawler = $this->client->submit($form);
        self::assertResponseIsSuccessful();

        $clientPublicKey = $crawler->filter('input#client_publicId')->attr('value');
        $clientSecretKey = $crawler->filter('input#client_secret')->attr('value');

        $this->logoutUser();

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

        self::assertResponseIsSuccessful();
        $payload     = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
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

        self::assertResponseIsSuccessful();
        Assert::assertStringContainsString('"users":[', $this->client->getResponse()->getContent());
    }
}
