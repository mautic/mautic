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
                'controller' => 'MauticDashboardBundle:Default:index'
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
    )
);