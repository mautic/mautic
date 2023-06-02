<?php

namespace Mautic\EmailBundle\DependencyInjection\EnvProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;
use Symfony\Component\Mailer\Transport\Dsn;

class MailerDsnEnvVarProcessor implements EnvVarProcessorInterface
{
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        $env = $getEnv($name);
        try {
            $dsn = Dsn::fromString($env);

            return $env;
        } catch (\InvalidArgumentException $e) {
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
