<?php

namespace MauticPlugin\MessengerRedisBundle\Helper;

use Mautic\CoreBundle\Helper\Dsn\Dsn;

class DsnRedisConvertor
{
    private const ALLOWED_OPTIONS = [
        'stream',
        'group',
        'auto_setup',
        'tls',
        'consumer',
        'delete_after_ack',
        'delete_after_reject',
        'lazy',
        'redeliver_timeout',
        'region',
    ];

    public static function convertArrayToDsnString(array $parameters): string
    {
        $dsn = new Dsn(
            'redis',
            $parameters['messenger_host'],
            null,
            null,
            $parameters['messenger_port'] ? (int) $parameters['messenger_port'] : null,
            [
            'path'       => $parameters['messenger_path'],
            'auto_setup' => $parameters['messenger_auto_setup'],
            'group'      => $parameters['messenger_group'],
            'tls'        => $parameters['messenger_tls'],
            ]
        );

        $dsnString = $dsn->getScheme().'://';
        if (!empty($dsn->getUser())) {
            $dsnString .= $dsn->getUser();
        }
        if (!empty($dsn->getPassword())) {
            $dsnString .= ':'.$dsn->getPassword();
        }
        if (!empty($dsn->getUser()) || !empty($dsn->getPassword())) {
            $dsnString .= '@';
        }
        $dsnString .= $dsn->getHost();
        if (!empty($dsn->getPort())) {
            $dsnString .= ':'.$dsn->getPort();
        }
        if (!empty($dsn->getOption('path'))) {
            $dsnString .= '/'.$dsn->getOption('path');
        }

        $options = [];
        foreach (self::ALLOWED_OPTIONS as $option) {
            if (null !== $dsn->getOption($option)) {
                $options[$option] = $dsn->getOption($option);
            }
        }
        if (!empty($options)) {
            $dsnString .= '?'.http_build_query($options);
        }

        return $dsnString;
    }
}
