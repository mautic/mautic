<?php

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
