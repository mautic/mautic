<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'routes'   => [
        'main' => [
            'mautic_stage_index'              => [
                'path'       => '/stages/{page}',
                'controller' => 'MauticStageBundle:Stage:index'
            ],
            'mautic_stage_action'             => [
                'path'       => '/stages/{objectAction}/{objectId}',
                'controller' => 'MauticStageBundle:Stage:execute'
            ]
        ],
        'api'  => [
            'mautic_api_getstages'   => [
                'path'       => '/stages',
                'controller' => 'MauticStageBundle:Api\StageApi:getEntities'
            ],
            'mautic_api_getstage'    => [
                'path'       => '/stages/{id}',
                'controller' => 'MauticStageBundle:Api\StageApi:getEntity'
            ]
        ]
    ],

    'menu'     => [
        'main' => [
            'mautic.stages.menu.index' => [
                'route'  => 'mautic_stage_index',
                'iconClass' => 'fa-tachometer',
                'access'    => ['stage:stages:view'],
                'priority'  => 25
            ]
        ]
    ],

    'categories' => [
        'stage' => null
    ],

    'services' => [
        'events' => [
            'mautic.stage.subscriber'            => [
                'class' => 'Mautic\StageBundle\EventListener\StageSubscriber'
            ],
            'mautic.stage.leadbundle.subscriber' => [
                'class' => 'Mautic\StageBundle\EventListener\LeadSubscriber'
            ],
            'mautic.stage.search.subscriber'     => [
                'class' => 'Mautic\StageBundle\EventListener\SearchSubscriber'
            ],
            'mautic.stage.dashboard.subscriber'  => [
                'class' => 'Mautic\StageBundle\EventListener\DashboardSubscriber'
            ],
        ],
        'forms'  => [
            'mautic.stage.type.form'                  => [
                'class'     => 'Mautic\StageBundle\Form\Type\StageType',
                'arguments' => 'mautic.factory',
                'alias'     => 'stage'
            ],
            'mautic.stage.type.action'                => [
                'class' => 'Mautic\StageBundle\Form\Type\StageActionType',
                'alias' => 'stageaction'
            ]
        ],
        'models' =>  [
            'mautic.asset.model.asset' => [
                'class' => 'Mautic\StageBundle\Model\StageModel',
                'arguments' => [
                    'mautic.lead.model.lead',
                    'session'
        ]
    ]
]
    ]
];
