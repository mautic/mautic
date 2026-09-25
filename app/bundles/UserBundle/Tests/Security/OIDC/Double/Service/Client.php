<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Double\Service;

use Mautic\UserBundle\Security\OIDC\Client\ClientInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Test double for OIDC client that doesn't make real HTTP requests.
 * Configure responses by setting static properties in tests.
 */
final class Client implements ClientInterface
{
    public static ?RedirectResponse $authenticateResponse = null;

    /** @var array<string, mixed> */
    public static array $verifiedClaimsResponse = [];

    /** @var array<string, mixed> */
    public static array $userInfoResponse = [];

    public static string $mappingFieldResponse = 'sub';

    public static bool $isAuthenticatedResponse = false;

    public static ?string $testConnectionResponse = null;

    public function authenticate(): ?RedirectResponse
    {
        return self::$authenticateResponse;
    }

    public function getVerifiedClaims(array $claims): array
    {
        if ([] === self::$verifiedClaimsResponse) {
            return [
                'sub' => 'test-subject-id',
                'email' => 'test@example.com',
                'preferred_username' => 'testuser',
            ];
        }

        return self::$verifiedClaimsResponse;
    }

    public function requestUserInfo(array $claims): array
    {
        if ([] === self::$userInfoResponse) {
            return [
                'sub' => 'test-subject-id',
                'email' => 'test@example.com',
                'preferred_username' => 'testuser',
            ];
        }

        return self::$userInfoResponse;
    }

    public function getMappingField(): string
    {
        return self::$mappingFieldResponse;
    }

    public function isAuthenticated(): bool
    {
        return self::$isAuthenticatedResponse;
    }

    public function testConnection(): ?string
    {
        return self::$testConnectionResponse;
    }

    /**
     * Reset all static state between tests.
     */
    public static function reset(): void
    {
        self::$authenticateResponse = null;
        self::$verifiedClaimsResponse = [];
        self::$userInfoResponse = [];
        self::$mappingFieldResponse = 'sub';
        self::$isAuthenticatedResponse = false;
        self::$testConnectionResponse = null;
    }
}
