<?php

namespace Mautic\CoreBundle\DependencyInjection\EnvProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class IntNullableProcessor implements EnvVarProcessorInterface
{
    public function getEnv(string $prefix, string $name, \Closure $getEnv): ?int
    {
        $env = $getEnv($name);

        return null === $env ? null : (int) $env;
    }

    public static function getProvidedTypes(): array
    {
        return [
            'intNullable' => 'string|int',
        ];
    }
}
