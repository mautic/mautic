<?php

declare(strict_types=1);

return [
    'menu' => [
        'main' => [
            'priority' => 100,
            'items'    => [
                'mautic.dashboard.menu.index' => [
                    'route'     => 'mautic_dashboard_index',
                    'iconClass' => 'ri-funds-fill',
                ],
            ],
        ],
    ],
    'parameters' => [
        'dashboard_import_dir'      => '%mautic.application_dir%/app/assets/dashboards',
        'dashboard_import_user_dir' => '%mautic.application_dir%/media/dashboards',
    ],
];
