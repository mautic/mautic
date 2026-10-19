<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\Service;

use Mautic\UserBundle\Exception\OidcAuthorizationException;
use Mautic\UserBundle\Security\OIDC\Client\OpenIDConnectBridge;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Exception\SessionUnavailableException;

final class OpenIDConnectBridgeTest extends TestCase
{
    private const PROVIDER_URL = 'https://idp.example.com';

    /**
     * @var array<string, mixed>
     */
    private array $originalRequest;

    protected function setUp(): void
    {
        $this->originalRequest = $_REQUEST;
        $_REQUEST              = [];
    }

    protected function tearDown(): void
    {
        $_REQUEST = $this->originalRequest;
    }

    public function testAuthenticateStoresAuthorizationUrlAndStateInSession(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $bridge  = $this->createBridge();
        $bridge->setSession($session);
        $bridge->addScope(['email']);
        $bridge->setRedirectURL('https://mautic.example.com/s/oidc/check');

        $this->assertFalse($bridge->authenticate());

        $authorizationUrl = $bridge->getAuthorizationUrl();
        $this->assertIsString($authorizationUrl);
        $this->assertStringStartsWith(self::PROVIDER_URL.'/authorize?', $authorizationUrl);

        parse_str((string) parse_url($authorizationUrl, PHP_URL_QUERY), $query);
        $this->assertSame('client_id', $query['client_id']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('https://mautic.example.com/s/oidc/check', $query['redirect_uri']);
        $this->assertSame('email openid', $query['scope']);
        $this->assertSame($session->get('openid_connect_state'), $query['state']);
        $this->assertSame($session->get('openid_connect_nonce'), $query['nonce']);
    }

    public function testAuthenticateWrapsProviderErrorIntoOidcAuthorizationException(): void
    {
        $_REQUEST['error'] = 'access_denied';

        $bridge = $this->createBridge();
        $bridge->setSession(new Session(new MockArraySessionStorage()));

        $this->expectException(OidcAuthorizationException::class);
        $this->expectExceptionMessage('Error: access_denied');

        $bridge->authenticate();
    }

    public function testAuthenticateWrapsStateMismatchIntoOidcAuthorizationException(): void
    {
        $_REQUEST['id_token'] = 'header.payload.signature';
        $_REQUEST['state']    = 'unexpected';

        $session = new Session(new MockArraySessionStorage());
        $session->set('openid_connect_state', 'expected');

        $bridge = $this->createBridge();
        $bridge->setAllowImplicitFlow(true);
        $bridge->setSession($session);

        $this->expectException(OidcAuthorizationException::class);
        $this->expectExceptionMessage('Unable to determine state');

        $bridge->authenticate();
    }

    public function testAuthenticateRequiresSession(): void
    {
        $this->expectException(SessionUnavailableException::class);

        $this->createBridge()->authenticate();
    }

    public function testConnectionReturnsNullWhenIssuerIsAvailable(): void
    {
        $bridge = $this->createBridge();
        $bridge->providerConfigParam(['issuer' => self::PROVIDER_URL]);

        $this->assertNull($bridge->testConnection());
    }

    private function createBridge(): OpenIDConnectBridge
    {
        $bridge = new OpenIDConnectBridge(self::PROVIDER_URL, 'client_id', 'client_secret');
        $bridge->providerConfigParam(['authorization_endpoint' => self::PROVIDER_URL.'/authorize']);

        return $bridge;
    }
}
