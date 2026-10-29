<?php

declare(strict_types=1);

use Mautic\PointBundle\Controller\Api\PointApiController;
use Mautic\PointBundle\Controller\Api\TriggerApiController;
use Mautic\PointBundle\Controller\Api\PointGroupsApiController;
use Mautic\PointBundle\Controller\Api\PointInsightApiController;
use Mautic\PointBundle\Entity\Point;

return [
    'routes' => [
        'api' => [
            'mautic_api_pointactionsstandard' => [
                'standard_entity' => true,
                'name'            => 'points',
                'path'            => '/points',
                'controller'      => PointApiController::class,
            ],
            'mautic_api_pointtriggersstandard' => [
                'standard_entity' => true,
                'name'            => 'triggers',
                'path'            => '/points/triggers',
                'controller'      => TriggerApiController::class,
            ],
            'mautic_api_pointgroupsstandard' => [
                'standard_entity' => true,
                'name'            => 'pointGroups',
                'path'            => '/points/groups',
                'controller'      => PointGroupsApiController::class,
            ],
            'mautic_api_pointinsightsstandard' => [
                'standard_entity' => true,
                'name'            => 'insights',
                'path'            => '/points/insights',
                'controller'      => PointInsightApiController::class,
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
            'class' => Point::class,
        ],
    ],
];
