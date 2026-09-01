<?php

declare(strict_types=1);

return [
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
