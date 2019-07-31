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
        $integration = $this->getIntegration();

        $this->token = $token;

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

        $this->token = $token;
    }

    /**
     * Delete the saved token data.
     */
    public function deleteToken(): void
    {
        $integration = $this->getIntegration();

        $this->token = null;
    }

    /**
     * Returns true if a token exists (although it may not be valid)
     *
     * @return bool
     */
    public function hasToken(): bool
    {
        return (bool) $this->token;
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