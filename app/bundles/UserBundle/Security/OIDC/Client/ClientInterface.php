<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\Client;

use Mautic\UserBundle\Exception\OidcAuthorizationException;
use Symfony\Component\HttpFoundation\RedirectResponse;

interface ClientInterface
{
    /**
     * @throws OidcAuthorizationException
     */
    public function isAuthenticated(): bool;

    /**
     * @throws OidcAuthorizationException
     */
    public function authenticate(): ?RedirectResponse;

    public function testConnection(): ?string;

    /**
     * get the verified claims from the id token.
     *
     * @param string[] $claims
     *
     * @return array<string, mixed>
     *
     * @throws OidcAuthorizationException
     */
    public function getVerifiedClaims(array $claims): array;

    /**
     * get the claims from the userinfo endpoint.
     *
     * @param string[] $claims
     *
     * @return array<string, mixed>
     *
     * @throws OidcAuthorizationException
     */
    public function requestUserInfo(array $claims): array;

    public function getMappingField(): string;
}
