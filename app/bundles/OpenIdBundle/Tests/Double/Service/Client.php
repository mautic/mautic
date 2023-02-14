<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Tests\Double\Service;

use Mautic\OpenIdBundle\Service\ClientInterface;
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
