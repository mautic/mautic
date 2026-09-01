<?php

declare(strict_types=1);

return [
    'menu' => [
        'main' => [
            'items' => [
                'mautic.dynamicContent.dynamicContent' => [
                    'route'    => 'mautic_dynamicContent_index',
                    'access'   => ['dynamiccontent:dynamiccontents:viewown', 'dynamiccontent:dynamiccontents:viewother'],
                    'parent'   => 'mautic.core.components',
                    'priority' => 90,
                ],
            ],
        ],
    ],
    'categories' => [
        'dynamicContent' => [
            'class' => Mautic\DynamicContentBundle\Entity\DynamicContent::class,
        ],
    ],
    'routes' => [
        'api' => [
            'mautic_api_dynamicContent_standard' => [
                'standard_entity' => true,
                'name'            => 'dynamicContents',
                'path'            => '/dynamiccontents',
                'controller'      => Mautic\DynamicContentBundle\Controller\Api\DynamicContentApiController::class,
            ],
        ],
    ],
];
