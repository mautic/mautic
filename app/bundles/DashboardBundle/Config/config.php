<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'routes' => array(
        'main' => array(
            'mautic_dashboard_index' => array(
                'path' => '/dashboard',
                'controller' => 'MauticDashboardBundle:Dashboard:index'
            ),
            'mautic_dashboard_action'       => array(
                'path'         => '/dashboard/{objectAction}/{objectId}',
                'controller'   => 'MauticDashboardBundle:Dashboard:execute'
            )
        ),
        'api' => array(
            'mautic_widget_types'             => array(
                'path'       => '/data',
                'controller' => 'MauticDashboardBundle:Api\WidgetApi:getTypes'
            ),
            'mautic_widget_data'             => array(
                'path'       => '/data/{type}',
                'controller' => 'MauticDashboardBundle:Api\WidgetApi:getData'
            )
        )
    ),

    'menu' => array(
        'main' => array(
            'priority' => 100,
            'items'    => array(
                'mautic.dashboard.menu.index' => array(
                    'route'     => 'mautic_dashboard_index',
                    'iconClass' => 'fa-th-large'
                )
            )
        )
    ),
    'services' => array(
        'events'  => array(
            // 'mautic.dashboard.subscriber' => array(
            //     'class' => 'Mautic\DashboardBundle\EventListener\DashboardSubscriber'
            // ),
        ),
        'forms'   => array(
            'mautic.dashboard.form.type.widget' => array(
                'class'     => 'Mautic\DashboardBundle\Form\Type\WidgetType',
                'arguments' => 'mautic.factory',
                'alias'     => 'widget'
            ),
            'mautic.dashboard.form.uplload' => array(
                'class'     => 'Mautic\DashboardBundle\Form\Type\UploadType',
                'arguments' => 'mautic.factory',
                'alias'     => 'dashboard_upload'
            ),
            'mautic.dashboard.form.filter' => array(
                'class'     => 'Mautic\DashboardBundle\Form\Type\FilterType',
                'arguments' => 'mautic.factory',
                'alias'     => 'dashboard_filter'
            )
        )
    ),
    'parameters' => array(
        'dashboard_import_dir'      => '%kernel.root_dir%/../media/dashboards',
        'dashboard_import_user_dir' => null
    )
);