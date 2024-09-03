<?php

namespace Mautic\CoreBundle\DependencyInjection\EnvProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class MauticConstProcessor implements EnvVarProcessorInterface
{
    public function getEnv(string $prefix, string $name, \Closure $getEnv): ?string
    {
        return defined($name) ? constant($name) : null;
    }

    public static function getProvidedTypes(): array
    {
        return [
            'mauticconst' => 'string',
        ];
    }
}
