<?php

declare(strict_types=1);

use MauticPlugin\MauticFocusBundle\Controller\Api\FocusApiController;
use MauticPlugin\MauticFocusBundle\Controller\FocusController;
use MauticPlugin\MauticFocusBundle\Controller\PublicController;
use MauticPlugin\MauticFocusBundle\Entity\Focus;

return [
    'name'        => 'Mautic Focus',
    'description' => 'Drive visitor\'s focus on your website with Mautic Focus',
    'version'     => '1.0',
    'author'      => 'Mautic, Inc',

    'routes' => [
        'main' => [
            'mautic_focus_index' => [
                'path'       => '/focus/{page}',
                'controller' => FocusController::class.'::indexAction',
            ],
            'mautic_focus_action' => [
                'path'       => '/focus/{objectAction}/{objectId}',
                'controller' => FocusController::class.'::executeAction',
            ],
        ],
        'public' => [
            'mautic_focus_generate' => [
                'path'       => '/focus/{id}.js',
                'controller' => PublicController::class.'::generateAction',
            ],
            'mautic_focus_pixel' => [
                'path'       => '/focus/{id}/viewpixel.gif',
                'controller' => PublicController::class.'::viewPixelAction',
            ],
        ],
        'api' => [
            'mautic_api_focusstandard' => [
                'standard_entity' => true,
                'name'            => 'focus',
                'path'            => '/focus',
                'controller'      => FocusApiController::class,
            ],
            'mautic_api_focusjs' => [
                'path'       => '/focus/{id}/js',
                'controller' => FocusApiController::class.'::generateJsAction',
                'method'     => 'POST',
            ],
        ],
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
        'plugin:focus' => [
            'label' => 'mautic.focus',
            'class' => Focus::class,
        ],
    ],

    'parameters' => [
        'website_snapshot_url' => 'https://mautic.net/api/snapshot',
        'website_snapshot_key' => '',
    ],
];
