<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Auth\Helper;

use MauticPlugin\IntegrationsBundle\Integration\Interfaces\AuthenticationInterface;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;

class AuthIntegrationsHelper
{
    /**
     * @var AuthenticationInterface[]
     */
    private $integrations = [];

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
     * @return AuthenticationInterface
     * @throws IntegrationNotFoundException
     */
    public function getIntegration(string $integration)
    {
        if (!isset($this->integrations[$integration])){
            throw new IntegrationNotFoundException("$integration either doesn't exist or has not been tagged with mautic.authentication_integration");
        }

        return $this->integrations[$integration];
    }
}