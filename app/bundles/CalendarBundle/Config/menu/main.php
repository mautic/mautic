<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
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
    'priority'  => 2,
    'items'     => $items
);