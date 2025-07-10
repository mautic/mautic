<?php

namespace Mautic\CoreBundle\DependencyInjection\EnvProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class NullableProcessor implements EnvVarProcessorInterface
{
    public function getEnv(string $prefix, string $name, \Closure $getEnv): ?string
    {
        $env = $getEnv($name);

        return '' === $env ? null : $env;
    }

    public static function getProvidedTypes(): array
    {
        return [
            'nullable' => 'string',
        ];
    }
}
