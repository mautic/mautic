<?php

return [
    'name'        => 'Mautic Focus',
    'description' => 'Drive visitor\'s focus on your website with Mautic Focus',
    'version'     => '1.0',
    'author'      => 'Mautic, Inc',

    'routes' => [
        'main' => [
            'mautic_focus_index' => [
                'path'       => '/focus/{page}',
                'controller' => 'MauticPlugin\MauticFocusBundle\Controller\FocusController::indexAction',
            ],
            'mautic_focus_action' => [
                'path'       => '/focus/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MauticFocusBundle\Controller\FocusController::executeAction',
            ],
        ],
        'public' => [
            'mautic_focus_generate' => [
                'path'       => '/focus/{id}.js',
                'controller' => 'MauticPlugin\MauticFocusBundle\Controller\PublicController::generateAction',
            ],
            'mautic_focus_pixel' => [
                'path'       => '/focus/{id}/viewpixel.gif',
                'controller' => 'MauticPlugin\MauticFocusBundle\Controller\PublicController::viewPixelAction',
            ],
        ],
        'api' => [
            'mautic_api_focusstandard' => [
                'standard_entity' => true,
                'name'            => 'focus',
                'path'            => '/focus',
                'controller'      => \MauticPlugin\MauticFocusBundle\Controller\Api\FocusApiController::class,
            ],
            'mautic_api_focusjs' => [
                'path'       => '/focus/{id}/js',
                'controller' => 'MauticPlugin\MauticFocusBundle\Controller\Api\FocusApiController::generateJsAction',
                'method'     => 'POST',
            ],
        ],
    ],

    'services' => [
        'other' => [
            'mautic.focus.helper.token' => [
                'class'     => \MauticPlugin\MauticFocusBundle\Helper\TokenHelper::class,
                'arguments' => [
                    'mautic.focus.model.focus',
                    'router',
                    'mautic.security',
                ],
            ],
            'mautic.focus.helper.iframe_availability_checker' => [
                'class'     => \MauticPlugin\MauticFocusBundle\Helper\IframeAvailabilityChecker::class,
                'arguments' => [
                    'translator',
                ],
            ],
        ],
//        'repositories' => [
//            'mautic.focus.repository' => [
//                'class'     => \Doctrine\ORM\EntityRepository::class,
//                'arguments' => \MauticPlugin\MauticFocusBundle\Entity\FocusRepository::class,
//                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
//            ],
//            'mautic.focus.stat.repository' => [
//                'class'     => \Doctrine\ORM\EntityRepository::class,
//                'arguments' => \MauticPlugin\MauticFocusBundle\Entity\StatRepository::class,
//                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
//            ],
//        ]
    ],

    'menu' => [
        'main' => [
            'mautic.focus' => [
                'route'    => 'mautic_focus_index',
                'access'   => 'focus:items:view',
                'parent'   => 'mautic.core.channels',
                'priority' => 10,
            ],
        ],
    ],

    'categories' => [
        'plugin:focus' => 'mautic.focus',
    ],

    'parameters' => [
        'website_snapshot_url' => 'https://mautic.net/api/snapshot',
        'website_snapshot_key' => '',
    ],
];
