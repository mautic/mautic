<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\OIDC\Client;

use Mautic\UserBundle\Security\OIDC\Exception\AuthorizationRequestFailedException;
use Symfony\Component\HttpFoundation\RedirectResponse;

interface ClientInterface
{
    /**
     * @throws AuthorizationRequestFailedException
     */
    public function isAuthenticated(): bool;

    /**
     * @throws AuthorizationRequestFailedException
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
     * @throws AuthorizationRequestFailedException
     */
    public function getVerifiedClaims(array $claims): array;

    /**
     * get the claims from the userinfo endpoint.
     *
     * @param string[] $claims
     *
     * @return array<string, mixed>
     *
     * @throws AuthorizationRequestFailedException
     */
    public function requestUserInfo(array $claims): array;

    public function getMappingField(): string;
}
