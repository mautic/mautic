<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\Client;

use Mautic\UserBundle\Exception\OidcAuthorizationException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface ClientBridgeInterface
{
    public function setSession(SessionInterface $session): void;

    public function getAuthorizationUrl(): ?string;

    /**
     * @throws OidcAuthorizationException
     */
    public function authenticate(): bool;

    /**
     * @return mixed
     */
    public function getVerifiedClaims(?string $claim = null);

    /**
     * @return mixed
     *
     * @throws OidcAuthorizationException
     */
    public function requestUserInfo(?string $claim = null);

    /**
     * Test the connection to the OIDC provider by checking the discovery endpoint.
     *
     * @return string|null Returns null on success, error message on failure
     */
    public function testConnection(): ?string;
}
