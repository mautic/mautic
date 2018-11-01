<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Helper;

use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\AuthenticationInterface;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;

class AuthIntegrationsHelper
{
    /**
     * @var AuthenticationInterface[]
     */
    private $integrations = [];

    /**
     * @var IntegrationsHelper
     */
    private $integrationsHelper;

    /**
     * AuthIntegrationsHelper constructor.
     *
     * @param IntegrationsHelper $integrationsHelper
     */
    public function __construct(IntegrationsHelper $integrationsHelper)
    {
        $this->integrationsHelper = $integrationsHelper;
    }

    /**
     * @param AuthenticationInterface $integration
     */
    public function addIntegration(AuthenticationInterface $integration)
    {
        $this->integrations[$integration->getName()] = $integration;
    }

    /**
     * @param string $integration
     *
     * @return IntegrationInterface
     * @throws IntegrationNotFoundException
     */
    public function getIntegration(string $integration)
    {
        if (!isset($this->integrations[$integration])){
            throw new IntegrationNotFoundException("$integration either doesn't exist or has not been tagged with mautic.authentication_integration");
        }

        // Ensure the configuration is hydrated
        $this->integrationsHelper->getIntegrationConfiguration($this->integrations[$integration]);

        return $this->integrations[$integration];
    }

    /**
     * @param Integration $integrationConfiguration
     */
    public function saveIntegrationConfiguration(Integration $integrationConfiguration)
    {
        $this->integrationsHelper->saveIntegrationConfiguration($integrationConfiguration);
    }
}