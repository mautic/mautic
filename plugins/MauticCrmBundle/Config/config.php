<?php

declare(strict_types=1);

use MauticPlugin\MauticCrmBundle\Controller\PublicController;

return [
    'name'        => 'CRM',
    'description' => 'Enables integration with Mautic supported CRMs.',
    'version'     => '1.0',
    'author'      => 'Mautic',
    'routes'      => [
        'public' => [
            'mautic_integration_contacts' => [
                'path'         => '/plugin/{integration}/contact_data',
                'controller'   => PublicController::class.'::contactDataAction',
                'requirements' => [
                    'integration' => '.+',
                ],
            ],
        ],
    ],
];
