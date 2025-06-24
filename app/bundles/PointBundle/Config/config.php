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
            'mautic_point.insight_index' => [
                'path'       => '/points/insights/{page}',
                'controller' => 'Mautic\PointBundle\Controller\InsightController::indexAction',
            ],
            'mautic_point.insight_action' => [
                'path'       => '/points/insights/{objectAction}/{objectId}',
                'controller' => 'Mautic\PointBundle\Controller\InsightController::executeAction',
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
                'controller'      => Mautic\PointBundle\Controller\Api\PointApiController::class,
            ],
            'mautic_api_getpointactiontypes' => [
                'path'       => '/points/actions/types',
                'controller' => 'Mautic\PointBundle\Controller\Api\PointApiController::getPointActionTypesAction',
            ],
            'mautic_api_pointtriggersstandard' => [
                'standard_entity' => true,
                'name'            => 'triggers',
                'path'            => '/points/triggers',
                'controller'      => Mautic\PointBundle\Controller\Api\TriggerApiController::class,
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
            'mautic_api_pointgroupsstandard' => [
                'standard_entity' => true,
                'name'            => 'pointGroups',
                'path'            => '/points/groups',
                'controller'      => Mautic\PointBundle\Controller\Api\PointGroupsApiController::class,
            ],
            'mautic_api_getcontactpointgroups' => [
                'path'       => '/contacts/{contactId}/points/groups',
                'controller' => 'Mautic\PointBundle\Controller\Api\PointGroupsApiController::getContactPointGroupsAction',
            ],
            'mautic_api_getcontactpointgroup' => [
                'path'       => '/contacts/{contactId}/points/groups/{groupId}',
                'controller' => 'Mautic\PointBundle\Controller\Api\PointGroupsApiController::getContactPointGroupAction',
            ],
            'mautic_api_adjustcontactgrouppoints' => [
                'path'       => '/contacts/{contactId}/points/groups/{groupId}/{operator}/{value}',
                'controller' => 'Mautic\PointBundle\Controller\Api\PointGroupsApiController::adjustGroupPointsAction',
                'method'     => 'POST',
            ],
            'mautic_api_pointinsightsstandard' => [
                'standard_entity' => true,
                'name'            => 'insights',
                'path'            => '/points/insights',
                'controller'      => Mautic\PointBundle\Controller\Api\PointInsightApiController::class,
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.points.menu.root' => [
                'id'        => 'mautic_points_root',
                'iconClass' => 'ri-coins-fill',
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
                    'mautic.point.insights.menu' => [
                        'route'  => 'mautic_point.insight_index',
                        'access' => 'point:insights:view',
                    ],
                ],
            ],
        ],
    ],

    'categories' => [
        'point' => [
            'class' => Mautic\PointBundle\Entity\Point::class,
        ],
    ],
];
