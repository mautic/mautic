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
        'main' => [
            'mautic_dynamicContent_index' => [
                'path'       => '/dwc/{page}',
                'controller' => 'Mautic\DynamicContentBundle\Controller\DynamicContentController::indexAction',
            ],
            'mautic_dynamicContent_action' => [
                'path'       => '/dwc/{objectAction}/{objectId}',
                'controller' => 'Mautic\DynamicContentBundle\Controller\DynamicContentController::executeAction',
            ],
        ],
        'public' => [
            'mautic_api_dynamicContent_index' => [
                'path'       => '/dwc',
                'controller' => 'Mautic\DynamicContentBundle\Controller\DynamicContentApiController::getAction',
            ],
            'mautic_api_dynamicContent_action' => [
                'path'       => '/dwc/{objectAlias}',
                'controller' => 'Mautic\DynamicContentBundle\Controller\DynamicContentApiController::processAction',
            ],
        ],
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
