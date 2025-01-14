<?php

$container->loadFromExtension(
    'leezy_pheanstalk',
    [
        'pheanstalks' => [
            'primary' => [
                'server'  => '%mautic.beanstalkd_host%',
                'port'    => '%mautic.beanstalkd_port%',
                'timeout' => '%mautic.beanstalkd_timeout%',
                'default' => true,
            ],
        ],
    ]
);
