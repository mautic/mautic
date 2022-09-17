<?php

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Helper\Dsn\Dsn;
use Mautic\CoreBundle\Helper\Dsn\DsnGenerator;

class MailerDsnConvertor
{
    /**
     * Includes the types that can be added as options to
     * the Dsn string.
     *
     * @var array
     */
    private const SUPPORTED_OPTIONS = [
        'encryption' => 'mailer_encryption',
        'auth_mode'  => 'mailer_auth_mode',
    ];

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

        foreach ($dsn->getOptions() as $option => $value) {
            $parameters['mailer_'.$option] = $value;
        }

        return $parameters;
    }

    public static function convertArrayToDsnString(array $parameters, bool $requirePassword = true): string
    {
        $host = self::getDefaultHost($parameters);
        if (empty($host)) {
            return '';
        }

        $options = [];
        foreach (self::SUPPORTED_OPTIONS as $option => $parameterName) {
            if (array_key_exists($parameterName, $parameters) && !empty($parameters[$parameterName])) {
                $options[$option] = $parameters[$parameterName];
            }
        }
        /**
         * We need to make sure that we have
         * both the user name and password before storing.
         * If the username is empty we need to flush the old password.
         */
        $password = (!empty($parameters['mailer_user'])) ? self::getPassword($parameters) : null;
        $user     = (!empty($parameters['mailer_user'])) ? $parameters['mailer_user'] : null;

        return DsnGenerator::getDsnString(
            new Dsn(
                $parameters['mailer_transport'],
                $host,
                $user,
                $password,
                self::getPort($parameters),
                $options
            )
        );
    }

    public static function getDefaultHost(array $parameters): string
    {
        if ('null' === $parameters['mailer_transport']) {
            return 'null';
        } elseif (empty($parameters['mailer_host'])) {
            return 'default';
        }

        return $parameters['mailer_host'];
    }

    public static function getPort(array $parameters): ?int
    {
        return $parameters['mailer_port'] ? (int) $parameters['mailer_port'] : null;
    }

    public static function getPassword(array $parameters): ?string
    {
        if (empty($parameters['mailer_password'])) {
            //The user did not change the password get it from the old Dsn String
            $oldString = self::convertDsnToArray($parameters['mailer_dsn']);

            return $oldString['mailer_password'];
        } else {
            return $parameters['mailer_password'];
        }
    }
}
