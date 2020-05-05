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
        'events' => [
            // 'mautic.dashboard.subscriber' => array(
            //     'class' => 'Mautic\DashboardBundle\EventListener\DashboardSubscriber'
            // ),
        ],
        'forms' => [
            'mautic.dashboard.form.type.widget' => [
                'class'     => 'Mautic\DashboardBundle\Form\Type\WidgetType',
                'arguments' => 'mautic.factory',
                'alias'     => 'widget',
            ],
            'mautic.dashboard.form.uplload' => [
                'class'     => 'Mautic\DashboardBundle\Form\Type\UploadType',
                'arguments' => 'mautic.factory',
                'alias'     => 'dashboard_upload',
            ],
            'mautic.dashboard.form.filter' => [
                'class'     => 'Mautic\DashboardBundle\Form\Type\FilterType',
                'arguments' => 'mautic.factory',
                'alias'     => 'dashboard_filter',
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
    ],
    'parameters' => [
        'dashboard_import_dir'      => '%kernel.root_dir%/../media/dashboards',
        'dashboard_import_user_dir' => null,
    ],
];
