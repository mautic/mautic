<?php

namespace MauticPlugin\MailerSesBundle\Helper;

use Mautic\CoreBundle\Helper\Dsn\Dsn;

class DsnSesConvertor
{
    private const ALLOWED_OPTIONS = [
        'region',
    ];

    public static function convertArrayToDsnString(array $parameters): string
    {
        $dsn = new Dsn(
            'ses+api',
            'default',
            $parameters['mailer_user'],
            $parameters['mailer_password'],
             null,
            [
            'region'       => $parameters['mailer_option_region'],
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
