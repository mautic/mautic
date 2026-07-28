<?php

declare(strict_types=1);

return [
    'name'        => 'Integrations',
    'description' => 'Adds support for plugin integrations',
    'author'      => 'Mautic, Inc.',
    'routes'      => [
        'main' => [
            'mautic_integration_config' => [
                'path'       => '/integration/{integration}/config',
                'controller' => 'Mautic\IntegrationsBundle\Controller\ConfigController::editAction',
            ],
            'mautic_integration_config_field_pagination' => [
                'path'       => '/integration/{integration}/config/{object}/{page}',
                'controller' => 'Mautic\IntegrationsBundle\Controller\FieldPaginationController::paginateAction',
                'defaults'   => [
                    'page' => 1,
                ],
            ],
            'mautic_integration_config_field_update' => [
                'path'       => '/integration/{integration}/config/{object}/field/{field}',
                'controller' => 'Mautic\IntegrationsBundle\Controller\UpdateFieldController::updateAction',
            ],
        ],
        'public' => [
            'mautic_integration_public_callback' => [
                'path'       => '/integration/{integration}/callback',
                'controller' => 'Mautic\IntegrationsBundle\Controller\AuthController::callbackAction',
            ],
        ],
    ],
    'services' => [
        'sync' => [
            'mautic.integrations.sync.service' => [
                'class'     => Mautic\IntegrationsBundle\Sync\SyncService\SyncService::class,
                'arguments' => [
                    'mautic.integrations.sync.data_exchange.mautic',
                    'mautic.integrations.helper.sync_date',
                    'mautic.integrations.helper.sync_mapping',
                    'mautic.integrations.sync.helper.relations',
                    'mautic.integrations.helper.sync_integrations',
                    'event_dispatcher',
                    'mautic.integrations.sync.notifier',
                    'mautic.integrations.sync.integration_process',
                    'mautic.integrations.sync.internal_process',
                ],
                'methodCalls' => [
                    'initiateDebugLogger' => ['mautic.sync.logger'],
                ],
            ],
        ],
    ],
];
