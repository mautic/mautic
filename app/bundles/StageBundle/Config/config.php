<?php

return [
    'routes' => [
        'main' => [
            'mautic_stage_index' => [
                'path'       => '/stages/{page}',
                'controller' => 'Mautic\StageBundle\Controller\StageController::indexAction',
            ],
            'mautic_stage_action' => [
                'path'       => '/stages/{objectAction}/{objectId}',
                'controller' => 'Mautic\StageBundle\Controller\StageController::executeAction',
            ],
        ],
        'api' => [
            'mautic_api_stagesstandard' => [
                'standard_entity' => true,
                'name'            => 'stages',
                'path'            => '/stages',
                'controller'      => \Mautic\StageBundle\Controller\Api\StageApiController::class,
            ],
            'mautic_api_stageddcontact' => [
                'path'       => '/stages/{id}/contact/{contactId}/add',
                'controller' => 'Mautic\StageBundle\Controller\Api\StageApiController::addContactAction',
                'method'     => 'POST',
            ],
            'mautic_api_stageremovecontact' => [
                'path'       => '/stages/{id}/contact/{contactId}/remove',
                'controller' => 'Mautic\StageBundle\Controller\Api\StageApiController::removeContactAction',
                'method'     => 'POST',
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.stages.menu.index' => [
                'route'     => 'mautic_stage_index',
                'iconClass' => 'fa-tachometer',
                'access'    => ['stage:stages:view'],
                'priority'  => 25,
            ],
        ],
    ],

    'categories' => [
        'stage' => null,
    ],

    'services' => [
        'repositories' => [
            'mautic.stage.repository.lead_stage_log' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\StageBundle\Entity\LeadStageLog::class,
                ],
            ],
            'mautic.stage.repository.stage' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\StageBundle\Entity\Stage::class,
                ],
            ],
        ],
    ],
];
