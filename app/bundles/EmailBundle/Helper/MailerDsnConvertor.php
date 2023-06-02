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
     * @var array<string, string>
     */
    private const SUPPORTED_OPTIONS = [
        'encryption'  => 'mailer_option_encryption',
        'auth_mode'   => 'mailer_option_auth_mode',
        'invalid_dsn' => 'mailer_option_invalid_dsn',
    ];

    /**
     * convert the mailer_* fields to a DSN string.
     *
     * @param string $dsnString The DSN string
     *
     * @return array<string, mixed> the array of DSN options
     */
    public static function convertDsnToArray(string $dsnString): array
    {
        $parameters = [];
        try {
            $dsn                            = Dsn::fromString($dsnString);
            $parameters['mailer_dsn']       = $dsnString;
            $parameters['mailer_transport'] = $dsn->getScheme();
            $parameters['mailer_host']      = $dsn->getHost();
            $parameters['mailer_port']      = $dsn->getPort();
            $parameters['mailer_user']      = $dsn->getUser();
            $parameters['mailer_password']  = $dsn->getPassword();

            foreach ($dsn->getOptions() as $option => $value) {
                $parameters['mailer_option_'.$option] = $value;
            }
        } catch (\InvalidArgumentException $e) {
            $dsn                                     = Dsn::fromString('smtp://localhost:25');
            $parameters['mailer_dsn']                = $dsnString;
            $parameters['mailer_transport']          = $dsn->getScheme();
            $parameters['mailer_host']               = $dsn->getHost();
            $parameters['mailer_port']               = $dsn->getPort();
            $parameters['mailer_user']               = $dsn->getUser();
            $parameters['mailer_password']           = $dsn->getPassword();
            $parameters['mailer_option_invalid_dsn'] = true;
        }

        return $parameters;
    }

    /**
     * Convert the DSN options to a DSN string.
     *
     * @param array<string> $parameters     The array of DSN options
     * @param array<string> $convertorClass The class to use for converting the DSN options
     *
     * @return string The DSN string
     */
    public static function convertArrayToDsnString(array $parameters, array $convertorClass = []): string
    {
        /**
         * We need to make sure that we have
         * both the user name and password before storing.
         * If the username is empty we need to flush the old password.
         */
        $password                      = (!empty($parameters['mailer_user'])) ? self::getPassword($parameters) : null;
        $user                          = (!empty($parameters['mailer_user'])) ? $parameters['mailer_user'] : null;
        $parameters['mailer_password'] = $password;

        if ('smtp' === $parameters['mailer_transport']) {
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
        } else {
            $class_name = $convertorClass[$parameters['mailer_transport']];
            $convertor  = new \ReflectionClass($class_name);
            $instance   = $convertor->newInstanceWithoutConstructor();

            return $instance->convertArrayToDsnString($parameters);
        }
    }

    /**
     * find the host of DSN string.
     *
     * @param array<string> $parameters The array of DSN options
     *
     * @return string The host of DSN string
     */
    public static function getDefaultHost(array $parameters): string
    {
        if ('null' === $parameters['mailer_transport']) {
            return 'null';
        } elseif (empty($parameters['mailer_host'])) {
            return 'default';
        }

        return $parameters['mailer_host'];
    }

    /**
     * find the port of DSN string.
     *
     * @param array<string> $parameters The array of DSN options
     *
     * @return int The port of DSN string
     */
    public static function getPort(array $parameters): ?int
    {
        return $parameters['mailer_port'] ? (int) $parameters['mailer_port'] : null;
    }

    /**
     * find the password of DSN string.
     *
     * @param array<string> $parameters The array of DSN options
     *
     * @return string The password of DSN string
     */
    public static function getPassword(array $parameters): ?string
    {
        if (empty($parameters['mailer_password'])) {
            // The user did not change the password get it from the old Dsn String
            $oldString = self::convertDsnToArray($parameters['mailer_dsn']);

            return $oldString['mailer_password'];
        } else {
            return $parameters['mailer_password'];
        }
    }
}
