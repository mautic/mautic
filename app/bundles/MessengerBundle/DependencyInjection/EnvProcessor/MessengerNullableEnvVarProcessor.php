<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\DependencyInjection\EnvProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class MessengerNullableEnvVarProcessor implements EnvVarProcessorInterface
{
    public function getEnv(string $prefix, string $name, \Closure $getEnv): string
    {
        return $getEnv($name) ?: 'null://';
    }

    public static function getProvidedTypes(): array
    {
        return [
            'messenger-nullable' => 'string',
        ];
    }
}
