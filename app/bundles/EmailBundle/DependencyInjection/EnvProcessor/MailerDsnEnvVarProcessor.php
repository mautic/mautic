<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\DependencyInjection\EnvProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Symfony\Component\Mailer\Transport\Dsn;

class MailerDsnEnvVarProcessor implements EnvVarProcessorInterface
{
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        $env = $getEnv($name);
        try {
            Dsn::fromString($env);

            return str_replace('%%', '%', $env);
        } catch (\InvalidArgumentException) {
            return 'invalid://null';
        }
    }

    public static function getProvidedTypes()
    {
        return [
            'mailer' => 'string',
        ];
    }
}
