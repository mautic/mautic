<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'routes' => [
        'main' => [
            'mautic_integration_config' => [
                'path'       => '/integration/{integration}/config/{page}',
                'controller' => 'IntegrationsBundle:Config:edit',
            ],
        ],
        'api' => [
        ],
    ],
    'menu' => [
    ],
    'services' => [
        'commands' => [
            'mautic.integrations.command.sync' => [
                'class'     => \MauticPlugin\IntegrationsBundle\Command\SyncCommand::class,
                'arguments' => [
                    'mautic.integrations.sync.service',
                ],
                'tag' => 'console.command',
            ],
        ],
        'events' => [
            'mautic.integrations.subscriber.lead' => [
                'class'     => \MauticPlugin\IntegrationsBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.integrations.repository.field_change',
                    'mautic.integrations.helper.variable_expresser',
                    'mautic.integrations.helper.sync_integrations',
                ],
            ],
            'mautic.integrations.subscriber.controller' => [
                'class' => \MauticPlugin\IntegrationsBundle\EventListener\ControllerSubscriber::class,
                'arguments' => [
                    'mautic.integrations.helper',
                    'controller_resolver',
                ],
            ],
        ],
        'forms' => [
            'mautic.integrations.form.config.integration' => [
                'class' => \MauticPlugin\IntegrationsBundle\Form\Type\IntegrationConfigType::class,
                'arguments' => [
                    'mautic.integrations.helper.config_integrations',
                ],
            ],
            'mautic.integrations.form.config.feature_settings' => [
                'class' => \MauticPlugin\IntegrationsBundle\Form\Type\IntegrationFeatureSettingsType::class,
            ],
            'mautic.integrations.form.config.sync_settings' => [
                'class' => \MauticPlugin\IntegrationsBundle\Form\Type\IntegrationSyncSettingsType::class,
            ],
            'mautic.integrations.form.config.sync_settings_field_mappings' => [
                'class' => \MauticPlugin\IntegrationsBundle\Form\Type\IntegrationSyncSettingsFieldMappingsType::class,
                'arguments' => [
                    'translator',
                ],
            ],
            'mautic.integrations.form.config.sync_settings_object_field_directions' => [
                'class' => \MauticPlugin\IntegrationsBundle\Form\Type\IntegrationSyncSettingsObjectFieldDirectionsType::class,
            ],
            'mautic.integrations.form.config.sync_settings_object_field_mapping' => [
                'class' => \MauticPlugin\IntegrationsBundle\Form\Type\IntegrationSyncSettingsObjectFieldMappingType::class,
            ],
            'mautic.integrations.form.config.sync_settings_field_directions' => [
                'class' => \MauticPlugin\IntegrationsBundle\Form\Type\IntegrationSyncSettingsFieldDirectionsType::class,
            ],
        ],
        'helpers' => [
            'mautic.integrations.helper.variable_expresser' => [
                'class' => \MauticPlugin\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelper::class,
            ],
            'mautic.integrations.helper' => [
                'class' => \MauticPlugin\IntegrationsBundle\Helper\IntegrationsHelper::class,
                'arguments' => [
                    'mautic.plugin.integrations.repository.integration',
                ],
            ],
            'mautic.integrations.helper.auth_integrations' => [
                'class' => \MauticPlugin\IntegrationsBundle\Helper\AuthIntegrationsHelper::class,
                'arugments' => [
                    'mautic.integrations.helper',
                ]
            ],
            'mautic.integrations.helper.sync_integrations' => [
                'class' => \MauticPlugin\IntegrationsBundle\Helper\SyncIntegrationsHelper::class,
                'arguments' => [
                    'mautic.integrations.helper',
                ],
            ],
            'mautic.integrations.helper.config_integrations' => [
                'class' => \MauticPlugin\IntegrationsBundle\Helper\ConfigIntegrationsHelper::class,
                'arguments' => [
                    'mautic.integrations.helper',
                ],
            ],
        ],
        'other' => [
            'mautic.service.encryption' => [
                'class' => \MauticPlugin\IntegrationsBundle\Facade\EncryptionService::class,
                'arguments' => [
                    'mautic.helper.encryption',
                ],
            ],
            'mautic.http.client' => [
                'class' => GuzzleHttp\Client::class
            ],
            'mautic.integration.auth_provider.oauth1atwolegged' => [
                'class' => \MauticPlugin\IntegrationsBundle\Auth\Provider\Oauth1aTwoLegged\HttpFactory::class,
            ],
        ],
        'repositories' => [
            'mautic.integrations.repository.field_change' => [
                'class'     => \Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\IntegrationsBundle\Entity\FieldChange::class,
                ],
            ],
            'mautic.integrations.repository.object_mapping' => [
                'class'     => \Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\IntegrationsBundle\Entity\ObjectMapping::class
                ],
            ],
            // Placeholder till the plugin bundle implements this
            'mautic.plugin.integrations.repository.integration' => [
                'class'     => \Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\PluginBundle\Entity\Integration::class
                ],
            ],
        ],
        'sync' => [
            'mautic.sync.logger' => [
                'class' =>  \MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger::class,
                'arguments' => [
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.integrations.helper.sync_judge' => [
                'class' => \MauticPlugin\IntegrationsBundle\Sync\SyncJudge\SyncJudge::class,
            ],
            'mautic.integrations.helper.contact_object' => [
                'class' => \MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\InternalObject\ContactObject::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.lead.repository.lead',
                    'doctrine.dbal.default_connection',
                ],
            ],
            'mautic.integrations.helper.company_object' => [
                'class' => \MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\InternalObject\CompanyObject::class,
                'arguments' => [
                    'mautic.lead.model.company',
                    'mautic.lead.repository.company',
                    'doctrine.dbal.default_connection',
                ],
            ],
            'mautic.integrations.sync.data_exchange.mautic' => [
                'class' => \MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange::class,
                'arguments' => [
                    'mautic.integrations.repository.field_change',
                    'mautic.integrations.helper.variable_expresser',
                    'mautic.integrations.helper.sync_mapping',
                    'mautic.integrations.helper.company_object',
                    'mautic.integrations.helper.contact_object',
                    'mautic.lead.model.field',
                ],
            ],
            'mautic.integrations.helper.sync_process_factory' => [
                'class' => \MauticPlugin\IntegrationsBundle\Sync\SyncProcess\SyncProcessFactory::class,
            ],
            'mautic.integrations.sync.service' => [
                'class' => \MauticPlugin\IntegrationsBundle\Sync\SyncService\SyncService::class,
                'arguments' => [
                    'mautic.integrations.helper.sync_judge',
                    'mautic.integrations.helper.sync_process_factory',
                    'mautic.integrations.helper.sync_date',
                    'mautic.integrations.sync.data_exchange.mautic',
                    'mautic.integrations.helper.sync_mapping',
                    'mautic.integrations.helper.sync_integrations',
                ],
                'methodCalls' => [
                    'initiateDebugLogger' => ['mautic.sync.logger'],
                ],
            ],
            'mautic.integrations.helper.sync_date' => [
                'class'     => \MauticPlugin\IntegrationsBundle\Sync\Helper\SyncDateHelper::class,
                'arguments' => [
                    'doctrine.dbal.default_connection',
                ],
            ],
            'mautic.integrations.helper.sync_mapping' => [
                'class' => \MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper::class,
                'arguments' => [
                    'mautic.lead.model.field',
                    'mautic.integrations.repository.object_mapping',
                    'mautic.integrations.helper.contact_object',
                    'mautic.integrations.helper.company_object',
                ],
            ],
        ],
    ],
];
