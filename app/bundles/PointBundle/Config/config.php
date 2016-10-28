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
            'mautic_pointtriggerevent_action' => [
                'path'       => '/points/triggers/events/{objectAction}/{objectId}',
                'controller' => 'MauticPointBundle:TriggerEvent:execute',
            ],
            'mautic_pointtrigger_index' => [
                'path'       => '/points/triggers/{page}',
                'controller' => 'MauticPointBundle:Trigger:index',
            ],
            'mautic_pointtrigger_action' => [
                'path'       => '/points/triggers/{objectAction}/{objectId}',
                'controller' => 'MauticPointBundle:Trigger:execute',
            ],
            'mautic_point_index' => [
                'path'       => '/points/{page}',
                'controller' => 'MauticPointBundle:Point:index',
            ],
            'mautic_point_action' => [
                'path'       => '/points/{objectAction}/{objectId}',
                'controller' => 'MauticPointBundle:Point:execute',
            ],
        ],
        'api' => [
            'mautic_api_pointactionsstandard' => [
                'standard_entity' => true,
                'name'            => 'points',
                'path'            => '/points',
                'controller'      => 'MauticPointBundle:Api\PointApi',
            ],
            'mautic_api_getpointactiontypes' => [
                'path'       => '/points/actions/types',
                'controller' => 'MauticPointBundle:Api\PointApi:getPointActionTypes',
            ],
            'mautic_api_pointtriggersstandard' => [
                'standard_entity' => true,
                'name'            => 'triggers',
                'path'            => '/points/triggers',
                'controller'      => 'MauticPointBundle:Api\TriggerApi',
            ],
            'mautic_api_getpointtriggereventtypes' => [
                'path'       => '/points/triggers/events/types',
                'controller' => 'MauticPointBundle:Api\TriggerApi:getPointTriggerEventTypes',
            ],
            'mautic_api_pointtriggerdeleteevents' => [
                'path'       => '/points/triggers/{triggerId}/events/delete',
                'controller' => 'MauticPointBundle:Api\TriggerApi:deletePointTriggerEvents',
                'method'     => 'DELETE',
            ],
            'mautic_api_adjustcontactpoints' => [
                'path'       => '/contacts/{leadId}/points/{operator}/{delta}',
                'controller' => 'MauticPointBundle:Api\PointApi:adjustPoints',
                'method'     => 'POST',
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.points.menu.root' => [
                'id'        => 'mautic_points_root',
                'iconClass' => 'fa-calculator',
                'access'    => ['point:points:view', 'point:triggers:view'],
                'priority'  => 30,
                'children'  => [
                    'mautic.point.menu.index' => [
                        'route'  => 'mautic_point_index',
                        'access' => 'point:points:view',
                    ],
                    'mautic.point.trigger.menu.index' => [
                        'route'  => 'mautic_pointtrigger_index',
                        'access' => 'point:triggers:view',
                    ],
                ],
            ],
        ],
    ],

    'categories' => [
        'point' => null,
    ],

    'services' => [
        'events' => [
            'mautic.point.subscriber' => [
                'class'     => 'Mautic\PointBundle\EventListener\PointSubscriber',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                ],
            ],
            'mautic.point.leadbundle.subscriber' => [
                'class'     => 'Mautic\PointBundle\EventListener\LeadSubscriber',
                'arguments' => [
                    'mautic.point.model.trigger',
                ],
            ],
            'mautic.point.search.subscriber' => [
                'class'     => 'Mautic\PointBundle\EventListener\SearchSubscriber',
                'arguments' => [
                    'mautic.point.model.point',
                    'mautic.point.model.trigger',
                ],
            ],
            'mautic.point.dashboard.subscriber' => [
                'class'     => 'Mautic\PointBundle\EventListener\DashboardSubscriber',
                'arguments' => [
                    'mautic.point.model.point',
                ],
            ],
            'mautic.point.stats.subscriber' => [
                'class'     => \Mautic\PointBundle\EventListener\StatsSubscriber::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'forms' => [
            'mautic.point.type.form' => [
                'class'     => 'Mautic\PointBundle\Form\Type\PointType',
                'arguments' => 'mautic.factory',
                'alias'     => 'point',
            ],
            'mautic.point.type.action' => [
                'class' => 'Mautic\PointBundle\Form\Type\PointActionType',
                'alias' => 'pointaction',
            ],
            'mautic.pointtrigger.type.form' => [
                'class'     => 'Mautic\PointBundle\Form\Type\TriggerType',
                'arguments' => 'mautic.factory',
                'alias'     => 'pointtrigger',
            ],
            'mautic.pointtrigger.type.action' => [
                'class' => 'Mautic\PointBundle\Form\Type\TriggerEventType',
                'alias' => 'pointtriggerevent',
            ],
            'mautic.point.type.genericpoint_settings' => [
                'class' => 'Mautic\PointBundle\Form\Type\GenericPointSettingsType',
                'alias' => 'genericpoint_settings',
            ],
        ],
        'models' => [
            'mautic.point.model.point' => [
                'class'     => 'Mautic\PointBundle\Model\PointModel',
                'arguments' => [
                    'session',
                    'mautic.helper.ip_lookup',
                    'mautic.lead.model.lead',
                ],
            ],
            'mautic.point.model.triggerevent' => [
                'class' => 'Mautic\PointBundle\Model\TriggerEventModel',
            ],
            'mautic.point.model.trigger' => [
                'class'     => 'Mautic\PointBundle\Model\TriggerModel',
                'arguments' => [
                    'mautic.helper.ip_lookup',
                    'mautic.lead.model.lead',
                    'mautic.point.model.triggerevent',
                ],
            ],
        ],
    ],
];
