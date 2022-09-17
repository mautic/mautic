<?php

namespace Mautic\EmailBundle\Mailer\Dsn;

class MailerDsnConvertor
{
    private const SUPPORTED_OPTIONS = [
        'region' => 'mailer_amazon_region',
    ];

    private const REPLACE_HOST = [
        'ses+api' => 'default',
    ];

    private const REMOVE_PORT = [
        'null',
        'ses+api',
    ];

    public static function convertDsnToArray(string $dsnString): array
    {
        $parameters = [];

        $dsn                                 = Dsn::fromString($dsnString);
        $parameters['mailer_dsn']            = $dsnString;
        $parameters['mailer_transport']      = $dsn->getScheme();
        $parameters['mailer_host']           = $dsn->getHost();
        $parameters['mailer_port']           = $dsn->getPort();
        $parameters['mailer_user']           = $dsn->getUser();
        $parameters['mailer_password']       = $dsn->getPassword();
        $parameters['mailer_amazon_region']  = $dsn->getOption('region');

        return $parameters;
    }

    public static function convertArrayToDsnString(array $parameters): string
    {
        $options = [];
        foreach (self::SUPPORTED_OPTIONS as $option => $parameterName) {
            if (array_key_exists($parameterName, $parameters) && !empty($parameters[$parameterName])) {
                $options[$option] = $parameters[$parameterName];
            }
        }

        return DsnGenerator::getDsnString(
            new Dsn(
                $parameters['mailer_transport'],
                self::getDefaultHost($parameters),
                $parameters['mailer_user'],
                $parameters['mailer_password'],
                self::getPort($parameters),
                $options
            )
        );
    }

    public static function getDefaultHost(array $parameters): string
    {
        if ('null' === $parameters['mailer_transport']) {
            return 'null';
        }

        foreach (self::REPLACE_HOST as $transport => $host) {
            if ($transport === $parameters['mailer_transport']) {
                return $host;
            }
        }

        if (empty($parameters['mailer_host'])) {
            return 'default';
        }

        return $parameters['mailer_host'];
    }

    public static function getPort(array $parameters): ?int
    {
        foreach (self::REMOVE_PORT as $transport) {
            if ($transport === $parameters['mailer_transport']) {
                return null;
            }
        }

        return $parameters['mailer_port'] ? (int) $parameters['mailer_port'] : null;
    }
}
