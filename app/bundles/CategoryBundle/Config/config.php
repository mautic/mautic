<?php

declare(strict_types=1);

return [
    'routes' => [
        'api' => [
            'mautic_api_categoriesstandard' => [
                'standard_entity' => true,
                'name'            => 'categories',
                'path'            => '/categories',
                'controller'      => Mautic\CategoryBundle\Controller\Api\CategoryApiController::class,
            ],
        ],
    ],

    'menu' => [
        'admin' => [
            'mautic.category.menu.index' => [
                'route'     => 'mautic_category_index',
                'access'    => 'category:categories:view',
                'iconClass' => 'ri-folder-6-line',
                'id'        => 'mautic_category_index',
                'parent'    => 'mautic.core.general',
                'priority'  => 20,
            ],
        ],
    ],
];
