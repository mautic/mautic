<?php

return [
    'routes' => [
        'main' => [
            'mautic_project_index' => [
                'path'       => '/projects/{page}',
                'controller' => 'Mautic\ProjectBundle\Controller\ProjectController::indexAction',
            ],
            'mautic_project_action' => [
                'path'       => '/projects/{objectAction}/{objectId}',
                'controller' => 'Mautic\ProjectBundle\Controller\ProjectController::executeAction',
            ],
        ],
        'api' => [
            'mautic_projectsstandard' => [
                'standard_entity' => true,
                'name'            => 'projects',
                'path'            => '/projects',
                'controller'      => 'MauticProjectBundle:Api\ProjectApi',
                'methods'         => 'GET',
            ],
        ],
    ],
    'services' => [
        'controllers' => [
            'mautic.project.controller.project' => [
                'class'     => Mautic\ProjectBundle\Controller\ProjectController::class,
                'arguments' => [
                    'mautic.project.model.project',
                    'form.factory',
                    'mautic.security',
                    'mautic.project.service.project_entity_loader',
                    'doctrine',
                ],
            ],
            'mautic.project.controller.ajax' => [
                'class'     => Mautic\ProjectBundle\Controller\AjaxController::class,
                'arguments' => [
                    'mautic.project.model.project',
                    'mautic.project.repository.project',
                    'mautic.security',
                ],
            ],
        ],
        'repositories' => [
            'mautic.project.repository.project' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    Mautic\ProjectBundle\Entity\Project::class,
                ],
            ],
        ],
        'models' => [
            'mautic.project.model.project' => [
                'class'     => Mautic\ProjectBundle\Model\ProjectModel::class,
                'arguments' => [
                    'mautic.project.service.project_entity_loader',
                ],
            ],
        ],
        'services' => [
            'mautic.project.service.project_entity_loader' => [
                'class'     => Mautic\ProjectBundle\Service\ProjectEntityLoaderService::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'translator',
                    'mautic.model.factory',
                    'mautic.security',
                    'event_dispatcher',
                ],
            ],
        ],
        'forms' => [
            'mautic.project.form.type.project' => [
                'class'     => Mautic\ProjectBundle\Form\Type\ProjectType::class,
                'arguments' => [
                    'translator',
                    'mautic.security',
                ],
            ],
        ],
        'events' => [
            'mautic.project.api.subscriber' => [
                'class' => Mautic\ProjectBundle\EventListener\ApiSubscriber::class,
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'project.menu.index' => [
                'id'        => Mautic\ProjectBundle\Controller\ProjectController::ROUTE_INDEX,
                'route'     => Mautic\ProjectBundle\Controller\ProjectController::ROUTE_INDEX,
                'access'    => Mautic\ProjectBundle\Security\Permissions\ProjectPermissions::CAN_VIEW,
                'iconClass' => 'ri-archive-stack-fill',
                'priority'  => 1,
            ],
        ],
    ],
];
