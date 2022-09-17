<?php

namespace Mautic\MessengerBundle\Helper;

use Mautic\CoreBundle\Helper\Dsn\Dsn;

class MessengerDsnConvertor
{
    public static function convertDsnToArray(string $dsnString): array
    {
        $parameters = [];

        $dsn                               = Dsn::fromString($dsnString);
        $parameters['messenger_dsn']       = $dsnString;
        $parameters['messenger_transport'] = 'mautic.messenger.'.$dsn->getScheme();
        $parameters['messenger_host']      = $dsn->getHost();
        $parameters['messenger_port']      = $dsn->getPort();
        $parameters['messenger_user']      = $dsn->getUser();
        $parameters['messenger_password']  = $dsn->getPassword();
        foreach ($dsn->getOptions() as $option => $value) {
            $parameters['messenger_'.$option] = $value;
        }

        return $parameters;
    }

    public static function convertArrayToDsnString(array $parameters, array $convertorClass): string
    {
        if ('mautic.messenger.doctrine' === $parameters['messenger_transport']) {
            /*
             * We will use a static Dsn string, that matches the default Dsn string
             * https://symfony.com/doc/current/messenger.html#doctrine-transport
             */
            return 'doctrine://default?table_name=table_name&queue_name=default&redeliver_timeout=3600&auto_setup=true';
        } else {
            $class_name = $convertorClass[$parameters['messenger_transport']];
            $convertor  = new \ReflectionClass($class_name);
            $instance   = $convertor->newInstanceWithoutConstructor();

            return $instance->convertArrayToDsnString($parameters);
        }
    }
}
