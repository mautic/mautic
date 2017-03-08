<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl3.0.html
 */

$container->loadFromExtension(
    'leezy_pheanstalk',
    [
        'pheanstalks' => [
            'primary' => [
                'server'             => '%mautic.beanstalkd_host%',
                'port'               => '%mautic.beanstalkd_port%',
                'timeout'            => '%mautic.beanstalkd_timeout%',
                'default'            => true,
            ],
        ],
    ]
);
