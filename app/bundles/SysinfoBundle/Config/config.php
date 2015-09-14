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
            'mautic_sysinfo_index' => array(
                'path' => '/sysinfo',
                'controller' => 'MauticSysinfoBundle:Sysinfo:index'
            )
        )
    ),

    'menu' => array(
        'admin' => array(
            'mautic.sysinfo.menu.index' => array(
                'route'           => 'mautic_sysinfo_index',
                'iconClass'       => 'fa-life-ring',
                'id'              => 'mautic_sysinfo_index',
                'access'          => 'admin'
            )
        )
    )
);
