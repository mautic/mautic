<?php

namespace Mautic\EmailBundle\Mailer\Dsn;

class MessengerDsnConvertor
{
    public static function convertDsnToArray(string $dsnString): array
    {
        $parameters = [];

        $dsn                                        = Dsn::fromString($dsnString);
        $parameters['mailer_messenger_dsn']         = $dsnString;
        $parameters['mailer_messenger_type']        = $dsn->getScheme();
        $parameters['mailer_spool_type']            = 'sync' === $dsn->getScheme() ? 'sync' : 'async';
        $parameters['mailer_messenger_host']        = $dsn->getHost();
        $parameters['mailer_messenger_port']        = $dsn->getPort();
        $parameters['mailer_messenger_stream']      = $dsn->getOption('path');
        $parameters['mailer_messenger_auto_setup']  = $dsn->getOption('auto_setup', true);
        $parameters['mailer_messenger_group']       = $dsn->getOption('group');
        $parameters['mailer_messenger_tls']         = $dsn->getOption('tls');

        return $parameters;
    }

    public static function convertArrayToDsnString(array $parameters): string
    {
        if ('sync' === $parameters['mailer_spool_type']) {
            return 'sync://';
        }

        if (empty($parameters['mailer_messenger_host'])) {
            return '';
        }

        return DsnGenerator::getDsnString(
            new Dsn(
                $parameters['mailer_messenger_type'],
                $parameters['mailer_messenger_host'],
                null,
                null,
                $parameters['mailer_messenger_port'] ? (int) $parameters['mailer_messenger_port'] : null,
                [
                    'path'       => $parameters['mailer_messenger_stream'],
                    'auto_setup' => $parameters['mailer_messenger_auto_setup'],
                    'group'      => $parameters['mailer_messenger_group'],
                    'tls'        => $parameters['mailer_messenger_tls'],
                ]
            )
        );
    }
}
