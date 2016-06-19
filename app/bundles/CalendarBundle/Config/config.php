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
            'mautic_calendar_index'  => array(
                'path'       => '/calendar',
                'controller' => 'MauticCalendarBundle:Default:index'
            ),
            'mautic_calendar_action' => array(
                'path'       => '/calendar/{objectAction}',
                'controller' => 'MauticCalendarBundle:Default:execute'
            )
        )
    ),
    'services' => array(
        'models' =>  array(
            'mautic.calendar.model.calendar' => array(
                'class' => 'Mautic\CalendarBundle\Model\CalendarModel'
            )
        )
    ),
    'menu'   => array(
        'main' => array(
            'priority' => 90,
            'items'    => array(
                'mautic.calendar.menu.index' => array(
                    'route'     => 'mautic_calendar_index',
                    'iconClass' => 'fa-calendar'
                )
            )
        )
    )
);
