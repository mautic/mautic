<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Double\Service;

use Mautic\UserBundle\Security\OIDC\Client\ClientInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class Client implements ClientInterface
{
    public function authenticate(): ?RedirectResponse
    {
        return null;
    }

    public function getVerifiedClaims(array $claims): array
    {
        return [];
    }

    public function requestUserInfo(array $claims): array
    {
        return [];
    }

    public function getMappingField(): string
    {
        return 'sub';
    }

    public function isAuthenticated(): bool
    {
        return true;
    }

    public function testConnection(): ?string
    {
        return null;
    }
}
