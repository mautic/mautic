<?php

namespace Mautic\EmailBundle\Mailer\Dsn;

class MailerDsnConvertor
{
    public static function convertDsnToArray(string $dsnString): array
    {
        $parameters = [];

        $dsn                            = Dsn::fromString($dsnString);
        $parameters['mailer_dsn']       = $dsnString;
        $parameters['mailer_transport'] = $dsn->getScheme();
        $parameters['mailer_host']      = $dsn->getHost();
        $parameters['mailer_port']      = $dsn->getPort();
        $parameters['mailer_user']      = $dsn->getUser();
        $parameters['mailer_password']  = $dsn->getPassword();

        return $parameters;
    }

    public static function convertArrayToDsnString(array $parameters): string
    {
        return DsnGenerator::getDsnString(
            new Dsn(
                $parameters['mailer_transport'],
                $parameters['mailer_host'],
                $parameters['mailer_user'],
                $parameters['mailer_password'],
                $parameters['mailer_port'] ? (int) $parameters['mailer_port'] : null
            )
        );
    }
}
