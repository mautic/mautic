<?php

namespace Mautic\CoreBundle\DependencyInjection\EnvProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class NullableProcessor implements EnvVarProcessorInterface
{
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        $env = $getEnv($name);

        return '' === $env ? null : $env;
    }

    public static function getProvidedTypes()
    {
        return [
            'nullable' => 'string',
        ];
    }
}
