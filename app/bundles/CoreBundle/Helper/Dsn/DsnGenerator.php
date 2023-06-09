<?php

namespace Mautic\CoreBundle\Helper\Dsn;

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
        if (!empty($dsn->getOption('path'))) {
            $dsnString .= '/'.$dsn->getOption('path');
        }

        $options = [];
        foreach ($dsn->getOptions() as $option => $value) {
            if (null !== $value) {
                $options[$option] = $dsn->getOption($option);
            }
        }
        if (!empty($options)) {
            $dsnString .= '?'.http_build_query($options);
        }

        return $dsnString;
    }
}
