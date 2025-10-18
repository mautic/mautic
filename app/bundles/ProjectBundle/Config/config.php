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
