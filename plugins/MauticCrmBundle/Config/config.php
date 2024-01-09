<?php

return [
    'name'        => 'CRM',
    'description' => 'Enables integration with Mautic supported CRMs.',
    'version'     => '1.0',
    'author'      => 'Mautic',
    'routes'      => [
        'public' => [
            'mautic_integration_contacts' => [
                'path'         => '/plugin/{integration}/contact_data',
                'controller'   => 'MauticPlugin\MauticCrmBundle\Controller\PublicController::contactDataAction',
                'requirements' => [
                    'integration' => '.+',
                ],
            ],
            'mautic_integration_companies' => [
                'path'         => '/plugin/{integration}/company_data',
                'controller'   => 'MauticPlugin\MauticCrmBundle\Controller\PublicController::companyDataAction',
                'requirements' => [
                    'integration' => '.+',
                ],
            ],
        ],
    ],
    'services' => [
        'other' => [
            'mautic_integration.service.transport' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Services\Transport::class,
                'arguments' => [
                    'mautic.http.client',
                ],
            ],
        ],
    ],
];
