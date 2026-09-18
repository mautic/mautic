<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\Client;

use Mautic\UserBundle\Security\OIDC\Exception\AuthorizationRequestFailedException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface ClientBridgeInterface
{
    public function setSession(SessionInterface $session): void;

    public function getAuthorizationUrl(): ?string;

    /**
     * @return bool
     *
     * @throws AuthorizationRequestFailedException
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
     * @throws AuthorizationRequestFailedException
     */
    public function requestUserInfo($claim = null);
}
