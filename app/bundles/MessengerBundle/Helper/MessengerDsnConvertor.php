<?php

namespace Mautic\MessengerBundle\Helper;

use Mautic\CoreBundle\Helper\Dsn\Dsn;

class MessengerDsnConvertor
{
    /**
     * return an array based on dsn string for any transport.
     *
     * @return array<string>
     */
    public static function convertDsnToArray(string $dsnString): array
    {
        $parameters = [];

        $dsn                              = Dsn::fromString($dsnString);
        $parameters['messenger_dsn']      = $dsnString;
        $parameters['messenger_type']     = 'mautic.messenger.'.$dsn->getScheme();
        $parameters['messenger_host']     = $dsn->getHost();
        $parameters['messenger_port']     = $dsn->getPort();
        $parameters['messenger_user']     = $dsn->getUser();
        $parameters['messenger_password'] = $dsn->getPassword();
        foreach ($dsn->getOptions() as $option => $value) {
            $parameters['messenger_'.$option] = $value;
        }

        return $parameters;
    }

    /**
     * return the dsn string for any transport.
     *
     * @param array<string> $parameters
     * @param array<string> $convertorClass
     */
    public static function convertArrayToDsnString(array $parameters, array $convertorClass): string
    {
        switch ($parameters['messenger_transport']) {
            case 'sync':
                return 'sync://';
            case 'doctrine':
                /*
                * We will use a static Dsn string, that matches the default Dsn string
                * https://symfony.com/doc/current/messenger.html#doctrine-transport
                */
                return 'doctrine://default';
            default:
                $class_name = $convertorClass[$parameters['messenger_transport']];
                $convertor  = new \ReflectionClass($class_name);
                $instance   = $convertor->newInstanceWithoutConstructor();

                return $instance->convertArrayToDsnString($parameters);
        }
    }
}
