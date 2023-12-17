<?php

return [
    'routes' => [
        'main' => [
            'mautic_pointtriggerevent_action' => [
                'path'       => '/points/triggers/events/{objectAction}/{objectId}',
                'controller' => 'Mautic\PointBundle\Controller\TriggerEventController::executeAction',
            ],
            'mautic_pointtrigger_index' => [
                'path'       => '/points/triggers/{page}',
                'controller' => 'Mautic\PointBundle\Controller\TriggerController::indexAction',
            ],
            'mautic_pointtrigger_action' => [
                'path'       => '/points/triggers/{objectAction}/{objectId}',
                'controller' => 'Mautic\PointBundle\Controller\TriggerController::executeAction',
            ],
            'mautic_point.group_index' => [
                'path'       => '/points/groups/{page}',
                'controller' => 'Mautic\PointBundle\Controller\GroupController::indexAction',
            ],
            'mautic_point.group_action' => [
                'path'       => '/points/groups/{objectAction}/{objectId}',
                'controller' => 'Mautic\PointBundle\Controller\GroupController::executeAction',
            ],
            'mautic_point_index' => [
                'path'       => '/points/{page}',
                'controller' => 'Mautic\PointBundle\Controller\PointController::indexAction',
            ],
            'mautic_point_action' => [
                'path'       => '/points/{objectAction}/{objectId}',
                'controller' => 'Mautic\PointBundle\Controller\PointController::executeAction',
            ],
        ],
        'api' => [
            'mautic_api_pointactionsstandard' => [
                'standard_entity' => true,
                'name'            => 'points',
                'path'            => '/points',
                'controller'      => \Mautic\PointBundle\Controller\Api\PointApiController::class,
            ],
            'mautic_api_getpointactiontypes' => [
                'path'       => '/points/actions/types',
                'controller' => 'Mautic\PointBundle\Controller\Api\PointApiController::getPointActionTypesAction',
            ],
            'mautic_api_pointtriggersstandard' => [
                'standard_entity' => true,
                'name'            => 'triggers',
                'path'            => '/points/triggers',
                'controller'      => \Mautic\PointBundle\Controller\Api\TriggerApiController::class,
            ],
            'mautic_api_getpointtriggereventtypes' => [
                'path'       => '/points/triggers/events/types',
                'controller' => 'Mautic\PointBundle\Controller\Api\TriggerApiController::getPointTriggerEventTypesAction',
            ],
            'mautic_api_pointtriggerdeleteevents' => [
                'path'       => '/points/triggers/{triggerId}/events/delete',
                'controller' => 'Mautic\PointBundle\Controller\Api\TriggerApiController::deletePointTriggerEventsAction',
                'method'     => 'DELETE',
            ],
            'mautic_api_adjustcontactpoints' => [
                'path'       => '/contacts/{leadId}/points/{operator}/{delta}',
                'controller' => 'Mautic\PointBundle\Controller\Api\PointApiController::adjustPointsAction',
                'method'     => 'POST',
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.points.menu.root' => [
                'id'        => 'mautic_points_root',
                'iconClass' => 'fa-calculator',
                'access'    => ['point:points:view', 'point:triggers:view', 'point:groups:view'],
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
                    'mautic.point.group.menu.index' => [
                        'route'  => 'mautic_point.group_index',
                        'access' => 'point:groups:view',
                    ],
                ],
            ],
        ],
    ],

    'categories' => [
        'point' => null,
    ],

    'services' => [
        'repositories' => [
            'mautic.point.repository.lead_point_log' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\PointBundle\Entity\LeadPointLog::class,
                ],
            ],
            'mautic.point.repository.lead_trigger_log' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\PointBundle\Entity\LeadTriggerLog::class,
                ],
            ],
        ],
    ],
];
