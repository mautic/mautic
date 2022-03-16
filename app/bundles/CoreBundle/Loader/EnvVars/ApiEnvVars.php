<?php

namespace Mautic\CoreBundle\Loader\EnvVars;

use Symfony\Component\HttpFoundation\ParameterBag;

class ApiEnvVars implements EnvVarsInterface
{
    public static function load(ParameterBag $config, ParameterBag $defaultConfig, ParameterBag $envVars): void
    {
        $accessTokenLifetime = $config->get('api_oauth2_access_token_lifetime');
        $envVars->set('MAUTIC_API_OAUTH2_ACCESS_TOKEN_LIFETIME', is_int($accessTokenLifetime) ? $accessTokenLifetime * 60 : 3600);

        $refreshTokenLifetime = $config->get('api_oauth2_refresh_token_lifetime');
        $envVars->set('MAUTIC_API_OAUTH2_REFRESH_TOKEN_LIFETIME', is_int($refreshTokenLifetime) ? $refreshTokenLifetime * 60 * 60 * 24 : 1209600);

        $apiRateLimitEnabled = 0 === (int) $config->get('api_rate_limiter_limit') ? false : true;
        $envVars->set('MAUTIC_API_RATE_LIMIT_ENABLED', $apiRateLimitEnabled);
    }
}
