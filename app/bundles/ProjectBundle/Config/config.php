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
            'mautic_project_batch_view' => [
                'path'       => '/projects/batch/entity/view',
                'controller' => 'Mautic\ProjectBundle\Controller\BatchProjectController::indexAction',
            ],
            'mautic_project_batch_set' => [
                'path'       => '/projects/batch/entity/set',
                'controller' => 'Mautic\ProjectBundle\Controller\BatchProjectController::execAction',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'project.menu.index' => [
                'id'        => Mautic\ProjectBundle\Controller\ProjectController::ROUTE_INDEX,
                'route'     => Mautic\ProjectBundle\Controller\ProjectController::ROUTE_INDEX,
                'access'    => Mautic\ProjectBundle\Security\Permissions\ProjectPermissions::CAN_VIEW,
                'iconClass' => 'ri-folder-2-fill',
                'priority'  => 1,
            ],
        ],
    ],
];
