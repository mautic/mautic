<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\DependencyInjection\EnvProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class MessengerNullableEnvVarProcessor implements EnvVarProcessorInterface
{
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        return $getEnv($name) ?: 'null://';
    }

    public static function getProvidedTypes()
    {
        return [
            'messenger-nullable' => 'string',
        ];
    }
}
