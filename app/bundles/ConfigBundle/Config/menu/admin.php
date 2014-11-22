<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$security = $event->getSecurity();

$items    = array();

if ($security->isGranted('config:config:full')) {
    $items['mautic.config.config.menu.index'] = array(
        'route'           => 'mautic_config_action',
        'routeParameters' => array('objectAction' => 'edit'),
        'linkAttributes'  => array(
            'data-toggle'    => 'ajax',
            'data-menu-link' => '#mautic_config_index',
            'id'             => 'mautic_config_index'
        ),
        'extras'          => array(
            'iconClass' => 'fa-cogs',
            'routeName' => 'mautic_config_index'
        )
    );
}

return $items;
