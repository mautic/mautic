<?php

return [
    'routes' => [
        'main' => [
            'mautic_dashboard_index' => [
                'path'       => '/dashboard',
                'controller' => 'Mautic\DashboardBundle\Controller\DashboardController::indexAction',
            ],
            'mautic_dashboard_widget' => [
                'path'       => '/dashboard/widget/{widgetId}',
                'controller' => 'Mautic\DashboardBundle\Controller\DashboardController::widgetAction',
            ],
            'mautic_dashboard_action' => [
                'path'       => '/dashboard/{objectAction}/{objectId}',
                'controller' => 'Mautic\DashboardBundle\Controller\DashboardController::executeAction',
            ],
        ],
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
                    'iconClass' => 'fa-th-large',
                ],
            ],
        ],
    ],
    'services' => [
        'other' => [
            'mautic.dashboard.widget' => [
                'class'     => \Mautic\DashboardBundle\Dashboard\Widget::class,
                'arguments' => [
                    'mautic.dashboard.model.dashboard',
                    'mautic.helper.user',
                    'session',
                ],
            ],
        ],
    ],
    'parameters' => [
        'dashboard_import_dir'      => '%mautic.application_dir%/app/assets/dashboards',
        'dashboard_import_user_dir' => '%mautic.application_dir%/media/dashboards',
    ],
];
