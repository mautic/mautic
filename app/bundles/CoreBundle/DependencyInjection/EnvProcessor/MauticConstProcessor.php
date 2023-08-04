<?php

namespace Mautic\CoreBundle\DependencyInjection\EnvProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class MauticConstProcessor implements EnvVarProcessorInterface
{
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        return defined($name) ? constant($name) : null;
    }

    public static function getProvidedTypes()
    {
        return [
            'mauticconst' => 'string',
        ];
    }
}
