<?php

declare(strict_types=1);

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
