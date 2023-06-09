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

            return $env;
        } catch (\InvalidArgumentException) {
            // if the DSN is invalid, return null transport and then show a warning
            return 'null://null';
        }
    }

    public static function getProvidedTypes()
    {
        return [
            'mailer' => 'string',
        ];
    }
}
