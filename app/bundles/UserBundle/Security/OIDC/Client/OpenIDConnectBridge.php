<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\Client;

use Jumbojett\OpenIDConnectClient;
use Jumbojett\OpenIDConnectClientException;
use Mautic\UserBundle\Security\OIDC\Exception\AuthorizationRequestFailedException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\SessionUnavailableException;

final class OpenIDConnectBridge extends OpenIDConnectClient implements ClientBridgeInterface
{
    private ?SessionInterface $session = null;
    private ?string $authRedirectURL   = null;

    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function getSessionKey($key)
    {
        $this->requireSession();

        return $this->session->get($key);
    }

    /**
     * instead of redirecting like the client store the url to prevent the request from exiting immediately.
     */
    public function redirect($url): void
    {
        $this->authRedirectURL = $url;
    }

    public function getAuthorizationUrl(): ?string
    {
        return $this->authRedirectURL;
    }

    /**
     * @throws AuthorizationRequestFailedException
     */
    public function authenticate(): bool
    {
        try {
            return parent::authenticate();
        } catch (OpenIDConnectClientException $e) {
            throw new AuthorizationRequestFailedException($e->getMessage());
        }
    }

    /**
     * @return mixed
     *
     * @throws AuthorizationRequestFailedException
     */
    public function requestUserInfo($claim = null)
    {
        try {
            return parent::requestUserInfo($claim);
        } catch (OpenIDConnectClientException $e) {
            throw new AuthorizationRequestFailedException($e->getMessage());
        }
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    protected function setSessionKey($key, $value): void
    {
        $this->requireSession();
        $this->session->set($key, $value);
    }

    /**
     * @param string $key
     */
    protected function unsetSessionKey($key): void
    {
        $this->requireSession();
        $this->session->remove($key);
    }

    private function requireSession(): void
    {
        if (null === $this->session) {
            throw new SessionUnavailableException('Session is not available.');
        }
    }
}
