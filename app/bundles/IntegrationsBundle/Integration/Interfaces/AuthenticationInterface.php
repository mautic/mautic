<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

use Symfony\Component\HttpFoundation\Request;

interface AuthenticationInterface extends IntegrationInterface
{
    /**
     * Returns true if the integration has already been authorized with the 3rd party service.
     */
    public function isAuthenticated(): bool;

    /**
     * This would be where one will use a client to store access tokens such as.
     *
     * @return string message to render if succeeded
     */
    public function authenticateIntegration(Request $request): string;
}
