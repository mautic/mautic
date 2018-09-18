<?php

namespace MauticPlugin\IntegrationsBundle\Integration\Interfaces;

use MauticPlugin\IntegrationsBundle\Auth\Exception\FailedToAuthenticateException;

interface AuthenticationInterface
{
    /**
     * Return the integration's name
     *
     * @return string
     */
    public function getName(): string;

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
