<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Loader\EnvVars;

use Mautic\CoreBundle\Loader\EnvVars\EnvVarsInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class MailerEnvLoader implements EnvVarsInterface
{
    /**
     * @param ParameterBag<string,string> $config
     * @param ParameterBag<string,string> $defaultConfig
     * @param ParameterBag<string,string> $envVars
     */
    public static function load(ParameterBag $config, ParameterBag $defaultConfig, ParameterBag $envVars): void
    {
        $envVars->set('MAUTIC_MAILER_DSN', $config->get('mailer_dsn'));
    }
}
