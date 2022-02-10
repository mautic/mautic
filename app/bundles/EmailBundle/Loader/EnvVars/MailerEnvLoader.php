<?php

namespace Mautic\EmailBundle\Loader\EnvVars;

use Mautic\CoreBundle\Loader\EnvVars\EnvVarsInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class MailerEnvLoader implements EnvVarsInterface
{
    public static function load(ParameterBag $config, ParameterBag $defaultConfig, ParameterBag $envVars): void
    {
        $envVars->set('MAUTIC_MAILER_DNS', $config->get('mailer_dsn'));
    }
}
