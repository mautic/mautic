<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\Client;

use Jumbojett\OpenIDConnectClient;
use Jumbojett\OpenIDConnectClientException;
use Mautic\UserBundle\Exception\OidcAuthorizationException;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\SessionUnavailableException;

#[Exclude]
final class OpenIDConnectBridge extends OpenIDConnectClient implements ClientBridgeInterface
{
    private ?SessionInterface $session = null;
    private ?string $authRedirectURL   = null;

    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }

    /**
     * @return mixed
     */
    protected function getSessionKey(string $key)
    {
        $this->requireSession();

        return $this->session->get($key);
    }

    /**
     * instead of redirecting like the client store the url to prevent the request from exiting immediately.
     */
    public function redirect(string $url): void
    {
        $this->authRedirectURL = $url;
    }

    public function getAuthorizationUrl(): ?string
    {
        return $this->authRedirectURL;
    }

    /**
     * @throws OidcAuthorizationException
     */
    public function authenticate(): bool
    {
        try {
            return parent::authenticate();
        } catch (OpenIDConnectClientException $e) {
            throw new OidcAuthorizationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return mixed
     *
     * @throws OidcAuthorizationException
     */
    public function requestUserInfo(?string $claim = null)
    {
        try {
            return parent::requestUserInfo($claim);
        } catch (OpenIDConnectClientException $e) {
            throw new OidcAuthorizationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param mixed $value
     */
    protected function setSessionKey(string $key, $value): void
    {
        $this->requireSession();
        $this->session->set($key, $value);
    }

    protected function unsetSessionKey(string $key): void
    {
        $this->requireSession();
        $this->session->remove($key);
    }

    public function testConnection(): ?string
    {
        try {
            // Test the connection by fetching the OIDC discovery document
            // This is the proper way to test OIDC connectivity without user interaction
            // We fetch authorization_endpoint instead of issuer because the constructor
            // pre-sets issuer to the provider URL, so checking it won't actually
            // contact the provider. Authorization endpoint is not pre-set and will
            // trigger a real fetch of .well-known/openid-configuration
            $this->getProviderConfigValue('authorization_endpoint');

            return null;
        } catch (OpenIDConnectClientException $e) {
            return $e->getMessage();
        }
    }

    private function requireSession(): void
    {
        if (null === $this->session) {
            throw new SessionUnavailableException('Session is not available.');
        }
    }
}
