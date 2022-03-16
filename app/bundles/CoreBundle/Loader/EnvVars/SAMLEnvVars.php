<?php

namespace Mautic\CoreBundle\Loader\EnvVars;

use Symfony\Component\HttpFoundation\ParameterBag;

class SAMLEnvVars implements EnvVarsInterface
{
    public static function load(ParameterBag $config, ParameterBag $defaultConfig, ParameterBag $envVars): void
    {
        if ($entityId = $config->get('saml_idp_entity_id')) {
            $envVars->set('MAUTIC_SAML_ENTITY_ID', $entityId);
        } elseif ($siteUrl = $config->get('site_url')) {
            $parts  = parse_url($siteUrl);
            $scheme = !empty($parts['scheme']) ? $parts['scheme'] : 'http';
            $envVars->set('MAUTIC_SAML_ENTITY_ID', $scheme.'://'.$parts['host']);
        } else {
            $envVars->set('MAUTIC_SAML_ENTITY_ID', 'mautic');
        }

        $samlEnabled = (bool) $config->get('saml_idp_metadata');

        $envVars->set('MAUTIC_SAML_LOGIN_PATH', $samlEnabled ? '/s/saml/login' : '/s/login');
        $envVars->set('MAUTIC_SAML_LOGIN_CHECK_PATH', $samlEnabled ? '/s/saml/login_check' : '/s/login_check');
    }
}
