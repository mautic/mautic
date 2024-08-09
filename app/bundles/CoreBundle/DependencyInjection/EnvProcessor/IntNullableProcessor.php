<?php

namespace Mautic\CoreBundle\DependencyInjection\EnvProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class IntNullableProcessor implements EnvVarProcessorInterface
{
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        $env = $getEnv($name);

        return null === $env ? null : (int) $env;
    }

    public static function getProvidedTypes()
    {
        return [
            'intNullable' => 'string|int',
        ];
    }
}
