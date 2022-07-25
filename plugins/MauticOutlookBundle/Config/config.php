<?php

return [
    'name'        => 'Outlook',
    'description' => 'Enables integrations with Outlook for email tracking',
    'version'     => '1.0',
    'author'      => 'Mautic',
    'services'    => [
        'integrations' => [
            'mautic.integration.outlook' => [
                'class'     => \MauticPlugin\MauticOutlookBundle\Integration\OutlookIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
    ],
];
