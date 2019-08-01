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
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotSetException;

class TokenPersistence implements TokenPersistenceInterface
{
    /**
     * @var EncryptionHelper
     */
    private $encryptionHelper;

    /**
     * @var Integration|null
     */
    private $integration;

    /**
     * @var IntegrationEntityRepository
     */
    private $integrationEntityRepository;

    /**
     * Token with decrypted data
     *
     * @var TokenInterface|null
     */
    private $token;

    /**
     * @param EncryptionHelper $encryptionHelper
     * @param IntegrationEntityRepository $integrationEntityRepository
     */
    public function __construct(EncryptionHelper $encryptionHelper, IntegrationEntityRepository $integrationEntityRepository)
    {
        $this->encryptionHelper = $encryptionHelper;
        $this->integrationEntityRepository = $integrationEntityRepository;
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

        $previousToken = new RawToken(
            $this->encryptionHelper->decrypt($apiKeys['access_token']),
            $this->encryptionHelper->decrypt($apiKeys['refresh_token']),
            $this->encryptionHelper->decrypt($apiKeys['expires_at'])
        );

        $refreshToken =  [
            'access_token' => $token->getAccessToken(),
            'refresh_token' => $token->getRefreshToken(),
            // Wee needs to use `expires_in` key because of merge algorithm
            'expires_in' => $token->getExpiresAt() - time(),
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

        $apiKeys = [
            'access_token'  => $this->encryptionHelper->encrypt($token->getAccessToken()),
            'refresh_token' => $this->encryptionHelper->encrypt($token->getRefreshToken()),
            'expires_at'    => $this->encryptionHelper->encrypt($token->getExpiresAt()),
        ];

        $integration->setApiKeys($apiKeys);
        $this->integrationEntityRepository->saveEntity($integration);

        $this->token = $token;
    }

    /**
     * Delete the saved token data.
     */
    public function deleteToken(): void
    {
        $integration = $this->getIntegration();

        $apiKeys = $integration->getApiKeys();

        unset($apiKeys['access_token'], $apiKeys['refresh_token'], $apiKeys['expires_at']);

        $integration->setApiKeys($apiKeys);

        $this->integrationEntityRepository->saveEntity($integration);

        $this->token = null;
    }

    /**
     * Returns true if a token exists (although it may not be valid)
     *
     * @return bool
     */
    public function hasToken(): bool
    {
        return $this->token && !empty($this->token->getAccessToken());
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