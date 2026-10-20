<?php

declare(strict_types=1);

return [
    'name'        => 'Clearbit',
    'description' => 'Enables integration with Clearbit for contact and company lookup',
    'version'     => '1.0',
    'author'      => 'Werner Garcia',

    'routes' => [
        'public' => [
            'mautic_plugin_clearbit_index' => [
                'path'       => '/clearbit/callback',
                'controller' => 'MauticPlugin\MauticClearbitBundle\Controller\PublicController::callbackAction',
            ],
        ],
        'main' => [
            'mautic_plugin_clearbit_action' => [
                'path'       => '/clearbit/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MauticClearbitBundle\Controller\ClearbitController::executeAction',
            ],
        ],
    ],
];
