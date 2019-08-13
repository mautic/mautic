<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Auth\Provider;

use kamermans\OAuth2\Persistence\TokenPersistenceInterface;
use kamermans\OAuth2\Token\RawToken;
use kamermans\OAuth2\Token\RawTokenFactory;
use kamermans\OAuth2\Token\TokenInterface;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotSetException;
use MauticPlugin\IntegrationsBundle\Helper\IntegrationsHelper;

class TokenPersistence implements TokenPersistenceInterface
{
    /**
     * @var IntegrationsHelper
     */
    private $integrationsHelper;

    /**
     * @var Integration|null
     */
    private $integration;

    /**
     * Token with decrypted data
     *
     * @var TokenInterface|null
     */
    private $token;

    /**
     * @param IntegrationsHelper $integrationsHelper
     */
    public function __construct(IntegrationsHelper $integrationsHelper)
    {
        $this->integrationsHelper = $integrationsHelper;
    }

    /**
     * Restore the token data into the give token.
     *
     * @param TokenInterface $token
     *
     * @return TokenInterface Restored token
     */
    public function restoreToken(TokenInterface $token): TokenInterface
    {
        $apiKeys = $this->getIntegration()->getApiKeys();

        if (!empty($apiKeys['access_token'])) {
            // Previous tokens exists
            $previousToken = new RawToken(
                $apiKeys['access_token'],
                $apiKeys['refresh_token'],
                $apiKeys['expires_at']
            );
        } else {
            $previousToken = new RawToken();
        }

        $refreshToken =  [
            'access_token' => $token->getAccessToken(),
            'refresh_token' => $token->getRefreshToken(),
            // Wee need to use `expires_in` key because of merge algorithm
            'expires_in' => $token->getExpiresAt() ? $token->getExpiresAt() - time() : - 1,
        ];

        // @see \kamermans\OAuth2\Token\RawTokenFactory::__invoke()
        $factory = new RawTokenFactory();
        $this->token = $factory($refreshToken, $previousToken);

        return $this->token;
    }

    /**
     * Save the token data.
     *
     * @param TokenInterface $token
     */
    public function saveToken(TokenInterface $token): void
    {
        $integration = $this->getIntegration();
        $oldApiKeys = $integration->getApiKeys();

        if ($oldApiKeys === null) {
            $oldApiKeys = [];
        }

        $newApiKeys = [
            'access_token'  => $token->getAccessToken(),
            'refresh_token' => $token->getRefreshToken(),
            'expires_at'    => $token->getExpiresAt(),
        ];

        $newApiKeys = array_merge($oldApiKeys, $newApiKeys);

        $integration->setApiKeys($newApiKeys);
        $this->integrationsHelper->saveIntegrationConfiguration($integration);

        $this->token = $token;
    }

    /**
     * Delete the saved token data.
     * @todo
     */
    public function deleteToken(): void
    {
        $integration = $this->getIntegration();

        $apiKeys = $integration->getApiKeys();

        unset($apiKeys['access_token'], $apiKeys['refresh_token'], $apiKeys['expires_at']);

        $integration->setApiKeys($apiKeys);

        $this->integrationsHelper->saveIntegrationConfiguration($integration);

        $this->token = null;
    }

    /**
     * Returns true if a token exists (although it may not be valid)
     *
     * @return bool
     */
    public function hasToken(): bool
    {
        return !empty($this->getIntegration()->getApiKeys()['access_token']);
    }

    /**
     * @param Integration $integration
     */
    public function setIntegration(Integration $integration): void
    {
        $this->integration = $integration;
    }

    /**
     * @return Integration
     *
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