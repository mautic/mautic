<?php

declare(strict_types=1);

use Mautic\ProjectBundle\Controller\ProjectController;
use Mautic\ProjectBundle\Security\Permissions\ProjectPermissions;

return [
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
