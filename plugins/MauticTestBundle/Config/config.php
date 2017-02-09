<?php

return [
    'name'        => 'Builder Test',
    'description' => 'test',
    'version'     => '1.0',
    'author'      => 'qneyrat',
    'type'        => 'builder',
    'routes'      => [
        'public' => [
            'mautic_plugin_test_builder' => [
                'path'       => '/test/builder',
                'controller' => 'MauticTestBundle:Public:builder',
            ],
        ],
    ],
];
