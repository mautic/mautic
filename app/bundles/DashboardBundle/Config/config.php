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
        )
    ),

    'menu' => array(
        'main' => array(
            'priority' => -100,
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
            'mautic.form.type.module' => array(
                'class'     => 'Mautic\DashboardBundle\Form\Type\ModuleType',
                'arguments' => 'mautic.factory',
                'alias'     => 'module'
            )
        )
    )
);