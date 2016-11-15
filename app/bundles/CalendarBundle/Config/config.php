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
            'mautic_calendar_index' => [
                'path'       => '/calendar',
                'controller' => 'MauticCalendarBundle:Default:index',
            ],
            'mautic_calendar_action' => [
                'path'       => '/calendar/{objectAction}',
                'controller' => 'MauticCalendarBundle:Default:execute',
            ],
        ],
    ],
    'services' => [
        'models' => [
            'mautic.calendar.model.calendar' => [
                'class' => 'Mautic\CalendarBundle\Model\CalendarModel',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'priority' => 90,
            'items'    => [
                'mautic.calendar.menu.index' => [
                    'route'     => 'mautic_calendar_index',
                    'iconClass' => 'fa-calendar',
                ],
            ],
        ],
    ],
];
