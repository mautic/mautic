<?php

declare(strict_types=1);

return [
    'name'        => 'FullContact',
    'description' => 'Enables integration with FullContact for contact and company lookup',
    'version'     => '1.0',
    'author'      => 'Mautic',

    'routes' => [
        'public' => [
            'mautic_plugin_fullcontact_index' => [
                'path'       => '/fullcontact/callback',
                'controller' => 'MauticPlugin\MauticFullContactBundle\Controller\PublicController::callbackAction',
            ],
        ],
        'main' => [
            'mautic_plugin_fullcontact_action' => [
                'path'       => '/fullcontact/{objectAction}/{objectId}',
                'controller' => 'MauticPlugin\MauticFullContactBundle\Controller\FullContactController::executeAction',
            ],
        ],
    ],
];
