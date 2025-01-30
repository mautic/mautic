<?php

namespace Mautic\CoreBundle\Loader\EnvVars;

use Symfony\Component\HttpFoundation\ParameterBag;

class SAMLEnvVars implements EnvVarsInterface
{
    public static function load(ParameterBag $config, ParameterBag $defaultConfig, ParameterBag $envVars): void
    {
        if ($entityId = $config->get('saml_idp_entity_id')) {
            $samlEntityId = $entityId;
        } elseif ($siteUrl = $config->get('site_url')) {
            $parts        = parse_url($siteUrl);
            $scheme       = !empty($parts['scheme']) ? $parts['scheme'] : 'http';
            $samlEntityId = $scheme.'://'.$parts['host'];
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
