<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Helper;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Integration\Interfaces\AuthenticationInterface;
use Mautic\PluginBundle\Entity\Integration;

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

    public function __construct(IntegrationsHelper $integrationsHelper)
    {
        $this->integrationsHelper = $integrationsHelper;
    }

    public function addIntegration(AuthenticationInterface $integration): void
    {
        $this->integrations[$integration->getName()] = $integration;
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function getIntegration(string $integration): AuthenticationInterface
    {
        if (!isset($this->integrations[$integration])) {
            throw new IntegrationNotFoundException("$integration either doesn't exist or has not been tagged with mautic.authentication_integration");
        }

        // Ensure the configuration is hydrated
        $this->integrationsHelper->getIntegrationConfiguration($this->integrations[$integration]);

        return $this->integrations[$integration];
    }

    public function saveIntegrationConfiguration(Integration $integrationConfiguration): void
    {
        $this->integrationsHelper->saveIntegrationConfiguration($integrationConfiguration);
    }
}
