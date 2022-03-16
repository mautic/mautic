<?php

namespace Mautic\CoreBundle\Loader\EnvVars;

use Symfony\Component\HttpFoundation\ParameterBag;

class SiteUrlEnvVars implements EnvVarsInterface
{
    public static function load(ParameterBag $config, ParameterBag $defaultConfig, ParameterBag $envVars): void
    {
        // Default all to null so that they are defined
        self::setNull($envVars);

        if (!$siteUrl = $config->get('site_url')) {
            return;
        }

        $parts = parse_url($siteUrl);

        // Host
        if (empty($parts['host'])) {
            return;
        }
        $envVars->set('MAUTIC_REQUEST_CONTEXT_HOST', $parts['host']);

        // Scheme
        $scheme = !empty($parts['scheme']) ? $parts['scheme'] : 'http';
        $envVars->set('MAUTIC_REQUEST_CONTEXT_SCHEME', $scheme);

        // Path
        if (!empty($parts['path'])) {
            $path = str_replace(['index_dev.php', 'index.php'], '', $parts['path']);

            // Check and remove trailing slash to prevent double // in Symfony cli generated URLs
            if ('/' == substr($path, -1)) {
                $path = substr($path, 0, -1);
            }

            $envVars->set('MAUTIC_REQUEST_CONTEXT_BASE_URL', $path);
        }

        // Port
        if (!empty($parts['port'])) {
            $portKey = ('http' === $scheme) ? 'MAUTIC_REQUEST_CONTEXT_HTTP_PORT' : 'MAUTIC_REQUEST_CONTEXT_HTTPS_PORT';
            $envVars->set($portKey, $parts['port']);
        }
    }

    private static function setNull(ParameterBag $envVars): void
    {
        $envVars->set('MAUTIC_REQUEST_CONTEXT_HOST', null);
        $envVars->set('MAUTIC_REQUEST_CONTEXT_SCHEME', null);
        $envVars->set('MAUTIC_REQUEST_CONTEXT_BASE_URL', null);
        $envVars->set('MAUTIC_REQUEST_CONTEXT_HTTP_PORT', 80);
        $envVars->set('MAUTIC_REQUEST_CONTEXT_HTTPS_PORT', 443);
    }
}
