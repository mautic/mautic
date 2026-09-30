<?php

namespace Mautic\CoreBundle\Loader\EnvVars;

use Symfony\Component\HttpFoundation\ParameterBag;

final class SAMLEnvVars implements EnvVarsInterface
{
    public static function load(ParameterBag $config, ParameterBag $defaultConfig, ParameterBag $envVars): void
    {
        if ($entityId = $config->get('saml_idp_entity_id')) {
            $samlEntityId = $entityId;
        } elseif ($siteUrl = $config->get('site_url')) {
            // parse_url() returns false for a malformed URL such as "http://",
            // and omits the host for one without an authority such as "mautic".
            // Neither can give an entity id, so fall back as if no URL were set.
            $parts = parse_url($siteUrl) ?: [];

            if (empty($parts['host'])) {
                $samlEntityId = 'mautic';
            } else {
                $scheme       = !empty($parts['scheme']) ? $parts['scheme'] : 'http';
                $samlEntityId = $scheme.'://'.$parts['host'];
            }
        } else {
            $samlEntityId = 'mautic';
        }

        $envVars->set('MAUTIC_SAML_ENTITY_ID', $samlEntityId);

        $samlEnabled = (bool) $config->get('saml_idp_metadata');
        $envVars->set('MAUTIC_SAML_ENABLED', $samlEnabled);

        $envVars->set('MAUTIC_SAML_LOGIN_PATH', '/s/saml/login');
        $envVars->set('MAUTIC_SAML_LOGIN_CHECK_PATH', '/s/saml/login_check');
    }
}
