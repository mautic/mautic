<?php

declare(strict_types=1);

use Mautic\ProjectBundle\Controller\Api\ProjectApiController;
use Mautic\ProjectBundle\Controller\ProjectController;
use Mautic\ProjectBundle\Security\Permissions\ProjectPermissions;

return [
    'routes' => [
        'api' => [
            'mautic_api_projectsstandard' => [
                'standard_entity' => true,
                'name'            => 'projects',
                'path'            => '/projects',
                'controller'      => ProjectApiController::class,
                'methods'         => 'GET',
            ],
        ],
    ],
    'menu' => [
        'main' => [
            'project.menu.index' => [
                'id'        => ProjectController::ROUTE_INDEX,
                'route'     => ProjectController::ROUTE_INDEX,
                'access'    => ProjectPermissions::CAN_VIEW,
                'iconClass' => 'ri-archive-stack-fill',
                'priority'  => 1,
            ],
        ],
    ],
];
