<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\DependencyInjection\EnvProcessor;

use Mautic\CoreBundle\Helper\Dsn\Dsn;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class MailerDsnEnvVarProcessor implements EnvVarProcessorInterface
{
    public function getEnv(string $prefix, string $name, \Closure $getEnv): string
    {
        $env = $getEnv($name);
        try {
            Dsn::fromString($env);

            return str_replace('%%', '%', $env);
        } catch (\InvalidArgumentException) {
            return 'invalid://null';
        }
    }

    public static function getProvidedTypes(): array
    {
        return [
            'mailer'         => 'string',
            'urlencoded-dsn' => 'string',
        ];
    }
}
