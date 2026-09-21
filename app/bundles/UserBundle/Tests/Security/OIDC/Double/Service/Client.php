<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Double\Service;

use Mautic\UserBundle\Security\OIDC\Client\ClientInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class Client implements ClientInterface
{
    /**
     * @inerhitDoc
     */
    public function authenticate(): ?RedirectResponse
    {
        return null;
    }

    /**
     * @inerhitDoc
     */
    public function getVerifiedClaims(array $claims): array
    {
        return [];
    }

    /**
     * @inerhitDoc
     */
    public function requestUserInfo(array $claims): array
    {
        return [];
    }

    /**
     * @inerhitDoc
     */
    public function getMappingField(): string
    {
        return 'sub';
    }

    /**
     * @inerhitDoc
     */
    public function isAuthenticated(): bool
    {
        return true;
    }

    /**
     * @inerhitDoc
     */
    public function testConnection(): ?string
    {
        return null;
    }
}
