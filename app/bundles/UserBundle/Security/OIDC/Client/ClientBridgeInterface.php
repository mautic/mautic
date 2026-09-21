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
     * @return bool
     *
     * @throws OidcAuthorizationException
     */
    public function authenticate();

    /**
     * @return mixed
     */
    public function getVerifiedClaims(?string $claim = null);

    /**
     * @param string|null $claim
     *
     * @return mixed
     *
     * @throws OidcAuthorizationException
     */
    public function requestUserInfo($claim = null);
}
