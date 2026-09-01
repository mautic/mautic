<?php

declare(strict_types=1);

return [
    'menu' => [
        'admin' => [
            'mautic.config.menu.index' => [
                'route'           => 'mautic_config_action',
                'routeParameters' => ['objectAction' => 'edit'],
                'iconClass'       => 'ri-settings-5-line',
                'id'              => 'mautic_config_index',
                'parent'          => 'mautic.core.general',
                'access'          => 'admin',
                'priority'        => 16,
            ],
            'mautic.sysinfo.menu.index' => [
                'route'     => 'mautic_sysinfo_index',
                'iconClass' => 'ri-information-2-line',
                'id'        => 'mautic_sysinfo_index',
                'parent'    => 'mautic.core.general',
                'access'    => 'admin',
                'priority'  => 04,
                'checks'    => [
                    'parameters' => [
                        'sysinfo_disabled' => false,
                    ],
                ],
            ],
        ],
    ],

    'parameters' => [
        'config_allowed_parameters' => [
            'kernel.project_dir',
            'kernel.logs_dir',
        ],
    ],
];
