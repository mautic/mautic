<?php

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

    'services' => [
        'others' => [
            'mautic.plugin.fullcontact.lookup_helper' => [
                'class'     => MauticPlugin\MauticFullContactBundle\Helper\LookupHelper::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.helper.user',
                    'monolog.logger.mautic',
                    'router',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.fullcontact' => [
                'class'     => MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'request_stack',
                    'router',
                    'translator',
                    'monolog.logger.mautic',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                    'mautic.lead.field.fields_with_unique_identifier',
                ],
            ],
        ],
    ],
];
