<?php

return [
    'routes'   => [
        'main' => [
            'mautic_core_ajax' => [
                'path'       => '/ajax',
                'controller' => 'Mautic\CoreBundle\Controller\AjaxController::delegateAjaxAction',
            ],
        ],
    ],
    'menu'     => [
        'main' => [
            'mautic.core.components' => [
                'id'        => 'mautic_components_root',
                'iconClass' => 'ri-puzzle-2-line',
                'priority'  => 60,
            ],
        ],
    ],
    'services' => [
        'helpers'  => [
            'mautic.helper.bundle' => [
                'class'     => Mautic\CoreBundle\Helper\BundleHelper::class,
                'arguments' => [
                    '%mautic.bundles%',
                    '%mautic.plugin.bundles%',
                ],
            ],
        ],
        'other'    => [
            'mautic.http.client' => [
                'class' => GuzzleHttp\Client::class,
            ],
        ],
        'fixtures' => [
            'mautic.test.fixture' => [
                'class'    => 'Foo\Bar\NonExisting',
                'optional' => true,
            ],
        ],
    ],

    'ip_lookup_services' => [
        'extreme-ip' => [
            'display_name' => 'Extreme-IP',
            'class'        => Mautic\CoreBundle\IpLookup\ExtremeIpLookup::class,
        ],
    ],

    'parameters' => [
        'log_path'      => '%kernel.project_dir%/var/logs',
        'max_log_files' => 7,
        'image_path'    => 'media/images',
        'bool_value'    => false,
        'null_value'    => null,
        'array_value'   => [],
    ],
];
