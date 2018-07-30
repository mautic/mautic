<?php

namespace MauticPlugin\MauticIntegrationsBundle\Auth\Provider;

use GuzzleHttp\Psr7\Request;
use MauticPlugin\MauticIntegrationsBundle\Integration\Interfaces\AuthenticationInterface;

interface AuthProviderInterface
{
    /**
     * Get the auth provider type.
     *
     * @return string
     */
    public function getAuthType();

    /**
     * Add appropriate auth headers to the request.
     *
     * @param Request                 $request
     * @param AuthenticationInterface $integration
     *
     * @return Request
     */
    public function authorizeRequest(Request $request, AuthenticationInterface $integration);
}
