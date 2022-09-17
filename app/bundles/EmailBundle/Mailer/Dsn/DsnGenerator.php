<?php

namespace Mautic\EmailBundle\Mailer\Dsn;

class DsnGenerator
{
    private const ALLOWED_OPTIONS = [
        'auto_setup',
        'group',
        'consumer',
        'delete_after_ack',
        'delete_after_reject',
        'lazy',
        'stream_max_entries',
        'tls',
        'redeliver_timeout',
    ];

    public static function getDsnString(Dsn $dsn): string
    {
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
