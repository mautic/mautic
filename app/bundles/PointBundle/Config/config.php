<?php
/**
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
            'mautic_api_getpoints' => [
                'path'       => '/points',
                'controller' => 'MauticPointBundle:Api\PointApi:getEntities',
            ],
            'mautic_api_getpoint' => [
                'path'       => '/points/{id}',
                'controller' => 'MauticPointBundle:Api\PointApi:getEntity',
            ],
            'mautic_api_gettriggers' => [
                'path'       => '/points/triggers',
                'controller' => 'MauticPointBundle:Api\TriggerApi:getEntities',
            ],
            'mautic_api_gettrigger' => [
                'path'       => '/points/triggers/{id}',
                'controller' => 'MauticPointBundle:Api\TriggerApi:getEntity',
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
