<?php

namespace Mautic\MessengerBundle\Helper;

use Mautic\CoreBundle\Helper\Dsn\Dsn;

class DsnDoctrineConvertor
{
    /**
     * return an array based on dsn string for doctrine transport.
     *
     * @return array<string>
     */
    public static function convertDsnToArray(string $dsnString): array
    {
        $parameters = [];

        $dsn                          = Dsn::fromString($dsnString);
        $parameters['messenger_dsn']  = $dsnString;
        $parameters['messenger_type'] = $dsn->getScheme();

        return $parameters;
    }

    /**
     * return the dsn string for doctrine transport.
     *
     * @param array<string> $parameters
     */
    public static function convertArrayToDsnString(array $parameters): string
    {
        /*
         * We will use a static Dsn string, that matches the default Dsn string
         * https://symfony.com/doc/current/messenger.html#doctrine-transport
         */
        return 'doctrine://default';
    }
}
