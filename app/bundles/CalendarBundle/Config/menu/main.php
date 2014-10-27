<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
/*
$items = array(
    'name' => array(
        'label'    => '',
        'route'    => '',
        'uri'      => '',
        'attributes' => array(),
        'labelAttributes' => array(),
        'linkAttributes' => array(),
        'childrenAttributes' => array(),
        'extras' => array(),
        'display' => true,
        'displayChildren' => true,
        'children' => array()
    )
);
 */

$items = array(
    'mautic.calendar.menu.index' => array(
        'name'    => 'mautic.calendar.menu.index',
        'route'    => 'mautic_calendar_index',
        'linkAttributes' => array(
            'data-toggle'    => 'ajax',
            'data-menu-link' => '#mautic_calendar_index',
            'id'             => 'mautic_calendar_index'
        ),
        'labelAttributes' => array(
            'class'   => 'nav-item-name'
        ),
        'extras'=> array(
            'iconClass' => 'fa-calendar',
            'routeName' => 'mautic_calendar_index'
        )
    )
);

return array(
    'priority' => 5,
    'items'    => $items
);