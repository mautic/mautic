<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'routes' => [
        'main' => [
            'mautic_dashboard_index' => [
                'path'       => '/dashboard',
                'controller' => 'MauticDashboardBundle:Dashboard:index',
            ],
            'mautic_dashboard_widget' => [
                'path'       => '/dashboard/widget/{widgetId}',
                'controller' => 'MauticDashboardBundle:Dashboard:widget',
            ],
            'mautic_dashboard_action' => [
                'path'       => '/dashboard/{objectAction}/{objectId}',
                'controller' => 'MauticDashboardBundle:Dashboard:execute',
            ],
        ],
        'api' => [
            'mautic_widget_types' => [
                'path'       => '/data',
                'controller' => 'MauticDashboardBundle:Api\WidgetApi:getTypes',
            ],
            'mautic_widget_data' => [
                'path'       => '/data/{type}',
                'controller' => 'MauticDashboardBundle:Api\WidgetApi:getData',
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
        'forms' => [
            'mautic.dashboard.form.type.widget' => [
                'class'     => 'Mautic\DashboardBundle\Form\Type\WidgetType',
                'arguments' => [
                    'event_dispatcher',
                    'mautic.security',
                ],
            ],
        ],
        'models' => [
            'mautic.dashboard.model.dashboard' => [
                'class'     => 'Mautic\DashboardBundle\Model\DashboardModel',
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.helper.paths',
                    'symfony.filesystem',
                ],
            ],
        ],
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
        'dashboard_import_dir'      => '%kernel.root_dir%/../media/dashboards',
        'dashboard_import_user_dir' => null,
    ],
];
