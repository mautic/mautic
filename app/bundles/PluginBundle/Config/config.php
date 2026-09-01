<?php

declare(strict_types=1);

return [
    'menu' => [
        'admin' => [
            'priority' => 50,
            'items'    => [
                'mautic.plugin.plugins' => [
                    'id'        => 'mautic_plugin_root',
                    'access'    => 'plugin:plugins:manage',
                    'route'     => 'mautic_plugin_index',
                    'parent'    => 'mautic.core.integrations',
                    'iconClass' => 'ri-plug-line',
                ],
            ],
        ],
    ],
];
