<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token;

use kamermans\OAuth2\Token\TokenInterface;
use kamermans\OAuth2\Token\TokenSerializer;

class IntegrationToken implements TokenInterface
{
    // Pull in serialize() and unserialize() methods
    use TokenSerializer;

    /**
     * @param mixed[] $extraData
     */
    public function __construct(
        ?string $accessToken,
        ?string $refreshToken,
        $expiresAt = null,
        private array $extraData = [],
    ) {
        $this->accessToken  = (string) $accessToken;
        $this->refreshToken = (string) $refreshToken;
        $this->expiresAt    = (int) $expiresAt;
    }

    /**
     * @return string The access token
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return string The refresh token
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * @return int The expiration timestamp
     */
    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        // Consider expired if there is not an access token
        if (!$this->getAccessToken()) {
            return true;
        }

        // Otherwise, consider expired if the expiration time has passed
        return $this->expiresAt && $this->expiresAt < time();
    }

    public function getExtraData(): array
    {
        return $this->extraData;
    }
}
