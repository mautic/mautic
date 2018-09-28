<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Integration\Interfaces;

use MauticPlugin\IntegrationsBundle\Auth\Exception\FailedToAuthenticateException;

interface AuthenticationInterface extends IntegrationInterface
{
    /**
     * Returns true if the integration has already been authorized with the 3rd party service
     *
     * @return bool
     */
    public function isAuthenticated(): bool;

    /**
     * Authenticate the integration with the 3rd party service
     *
     * @throws FailedToAuthenticateException
     */
    public function authenticateIntegration(): bool;
}
