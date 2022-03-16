<?php

namespace Mautic\CoreBundle\Loader\EnvVars;

use Symfony\Component\HttpFoundation\ParameterBag;

class MigrationsEnvVars implements EnvVarsInterface
{
    public static function load(ParameterBag $config, ParameterBag $defaultConfig, ParameterBag $envVars): void
    {
        $prefix = $config->get('db_table_prefix');
        $envVars->set('MAUTIC_MIGRATIONS_TABLE_NAME', $prefix.'migrations');
    }
}
