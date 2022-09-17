<?php

namespace Mautic\EmailBundle\Mailer\Dsn;

use Symfony\Component\Mailer\Transport\Dsn;

class DsnGenerator
{
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

        return $dsnString;
    }
}
