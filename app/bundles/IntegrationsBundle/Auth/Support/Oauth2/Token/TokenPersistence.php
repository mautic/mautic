<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token;

use kamermans\OAuth2\Persistence\TokenPersistenceInterface;
use kamermans\OAuth2\Token\TokenInterface;
use Mautic\IntegrationsBundle\Exception\IntegrationNotSetException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;

class TokenPersistence implements TokenPersistenceInterface
{
    private ?Integration $integration = null;

    public function __construct(
        private IntegrationsHelper $integrationsHelper,
    ) {
    }

    /**
     * Restore the token data into the give token.
     *
     * @return TokenInterface|IntegrationToken Restored token
     */
    public function restoreToken(TokenInterface $token): TokenInterface
    {
        $apiKeys               = $this->getIntegration()->getApiKeys();
        $apiKeys['expires_at'] ??= null;

        return new IntegrationToken(
            empty($apiKeys['access_token']) ? null : $apiKeys['access_token'],
            empty($apiKeys['refresh_token']) ? null : $apiKeys['refresh_token'],
            $apiKeys['expires_at'] ?? null
        );
    }

    /**
     * Save the token data.
     */
    public function saveToken(TokenInterface $token): void
    {
        $integration = $this->getIntegration();
        $oldApiKeys  = $integration->getApiKeys();

        if (null === $oldApiKeys) {
            $oldApiKeys = [];
        }

        $newApiKeys = [
            'access_token'  => $token->getAccessToken(),
            'refresh_token' => $token->getRefreshToken(),
            'expires_at'    => $token->getExpiresAt(),
        ];

        $extraData  = $token instanceof IntegrationToken ? $token->getExtraData() : [];
        $newApiKeys = array_merge($oldApiKeys, $extraData, $newApiKeys);

        $integration->setApiKeys($newApiKeys);
        $this->integrationsHelper->saveIntegrationConfiguration($integration);
    }

    /**
     * Delete the saved token data.
     */
    public function deleteToken(): void
    {
        $integration = $this->getIntegration();

        $apiKeys = $integration->getApiKeys();

        // Must delete both the access token and the expiration in order for the middleware to refresh
        unset($apiKeys['access_token'], $apiKeys['expires_at']);

        $integration->setApiKeys($apiKeys);

        $this->integrationsHelper->saveIntegrationConfiguration($integration);
    }

    /**
     * Returns true if a token exists (although it may not be valid).
     */
    public function hasToken(): bool
    {
        return !empty($this->getIntegration()->getApiKeys()['access_token']);
    }

    public function setIntegration(Integration $integration): void
    {
        $this->integration = $integration;
    }

    /**
     * @throws IntegrationNotSetException
     */
    private function getIntegration(): Integration
    {
        if ($this->integration) {
            return $this->integration;
        }

        throw new IntegrationNotSetException('Integration not set');
    }
}
