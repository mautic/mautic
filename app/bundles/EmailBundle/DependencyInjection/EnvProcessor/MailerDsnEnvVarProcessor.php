<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\DependencyInjection\EnvProcessor;

use Mautic\CoreBundle\Helper\Dsn\Dsn;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

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
<<<<<<< HEAD
            'mailer'         => 'string',
=======
            'mailer' => 'string',
>>>>>>> abc7f835bd (Fix using messenger DSN with special characters)
            'urlencoded-dsn' => 'string',
        ];
    }
}
