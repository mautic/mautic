<?php

declare(strict_types=1);

return [
    'routes' => [
        'api' => [
            'mautic_widget_types' => [
                'path'       => '/data',
                'controller' => 'Mautic\DashboardBundle\Controller\Api\WidgetApiController::getTypesAction',
            ],
            'mautic_widget_data' => [
                'path'       => '/data/{type}',
                'controller' => 'Mautic\DashboardBundle\Controller\Api\WidgetApiController::getDataAction',
            ],
        ],
    ],

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
