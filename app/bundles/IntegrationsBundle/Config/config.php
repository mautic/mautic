<?php

declare(strict_types=1);

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Integrations',
    'description' => 'Adds support for plugin integrations',
    'author'      => 'Mautic, Inc.',
    'routes'      => [
        'main' => [
            'mautic_integration_config' => [
                'path'       => '/integration/{integration}/config',
                'controller' => 'IntegrationsBundle:Config:edit',
            ],
            'mautic_integration_config_field_pagination' => [
                'path'       => '/integration/{integration}/config/{object}/{page}',
                'controller' => 'IntegrationsBundle:FieldPagination:paginate',
                'defaults'   => [
                    'page' => 1,
                ],
            ],
            'mautic_integration_config_field_update' => [
                'path'       => '/integration/{integration}/config/{object}/field/{field}',
                'controller' => 'IntegrationsBundle:UpdateField:update',
            ],
        ],
        'public' => [
            'mautic_integration_public_callback' => [
                'path'       => '/integration/{integration}/callback',
                'controller' => 'IntegrationsBundle:Auth:callback',
            ],
        ],
    ],
    'services' => [
        'commands' => [
            'mautic.integrations.command.sync' => [
                'class'     => \Mautic\IntegrationsBundle\Command\SyncCommand::class,
                'arguments' => [
                    'mautic.integrations.sync.service',
                    'mautic.helper.core_parameters',
                ],
                'tag' => 'console.command',
            ],
        ],
        'events' => [
            'mautic.integrations.subscriber.lead' => [
                'class'     => \Mautic\IntegrationsBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.integrations.repository.field_change',
                    'mautic.integrations.repository.object_mapping',
                    'mautic.integrations.helper.variable_expresser',
                    'mautic.integrations.helper.sync_integrations',
                ],
            ],
            'mautic.integrations.subscriber.contact_object' => [
                'class'     => \Mautic\IntegrationsBundle\EventListener\ContactObjectSubscriber::class,
                'arguments' => [
                    'mautic.integrations.helper.contact_object',
                    'router',
                ],
            ],
            'mautic.integrations.subscriber.company_object' => [
                'class'     => \Mautic\IntegrationsBundle\EventListener\CompanyObjectSubscriber::class,
                'arguments' => [
                    'mautic.integrations.helper.company_object',
                    'router',
                ],
            ],
            'mautic.integrations.subscriber.controller' => [
                'class'     => \Mautic\IntegrationsBundle\EventListener\ControllerSubscriber::class,
                'arguments' => [
                    'mautic.integrations.helper',
                    'controller_resolver',
                ],
            ],
            'mautic.integrations.subscriber.ui_contact_integrations_tab' => [
                'class'     => \Mautic\IntegrationsBundle\EventListener\UIContactIntegrationsTabSubscriber::class,
                'arguments' => [
                    'mautic.integrations.repository.object_mapping',
                ],
            ],
            'mautic.integrations.subscriber.contact_timeline_events' => [
                'class'     => \Mautic\IntegrationsBundle\EventListener\TimelineSubscriber::class,
                'arguments' => [
                    'mautic.lead.repository.lead_event_log',
                    'translator',
                ],
            ],
            'mautic.integrations.subscriber.email_subscriber' => [
                'class'     => \Mautic\IntegrationsBundle\EventListener\EmailSubscriber::class,
                'arguments' => [
                    'translator',
                    'event_dispatcher',
                    'mautic.integrations.token.parser',
                    'mautic.integrations.repository.object_mapping',
                    'mautic.helper.integration',
                ],
            ],
        ],
        'forms' => [
            'mautic.integrations.form.config.integration' => [
                'class'     => \Mautic\IntegrationsBundle\Form\Type\IntegrationConfigType::class,
                'arguments' => [
                    'mautic.integrations.helper.config_integrations',
                ],
            ],
            'mautic.integrations.form.config.feature_settings' => [
                'class' => \Mautic\IntegrationsBundle\Form\Type\IntegrationFeatureSettingsType::class,
            ],
            'mautic.integrations.form.config.sync_settings' => [
                'class' => \Mautic\IntegrationsBundle\Form\Type\IntegrationSyncSettingsType::class,
            ],
            'mautic.integrations.form.config.sync_settings_field_mappings' => [
                'class'     => \Mautic\IntegrationsBundle\Form\Type\IntegrationSyncSettingsFieldMappingsType::class,
                'arguments' => [
                    'monolog.logger.mautic',
                    'translator',
                ],
            ],
            'mautic.integrations.form.config.sync_settings_object_field_directions' => [
                'class' => \Mautic\IntegrationsBundle\Form\Type\IntegrationSyncSettingsObjectFieldType::class,
            ],
            'mautic.integrations.form.config.sync_settings_object_field_mapping' => [
                'class'     => \Mautic\IntegrationsBundle\Form\Type\IntegrationSyncSettingsObjectFieldMappingType::class,
                'arguments' => [
                    'translator',
                    'mautic.integrations.sync.data_exchange.mautic.field_helper',
                ],
            ],
            'mautic.integrations.form.config.sync_settings_object_field' => [
                'class' => \Mautic\IntegrationsBundle\Form\Type\IntegrationSyncSettingsObjectFieldType::class,
            ],
            'mautic.integrations.form.config.feature_settings.activity_list' => [
                'class'     => \Mautic\IntegrationsBundle\Form\Type\ActivityListType::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                ],
            ],
        ],
        'helpers' => [
            'mautic.integrations.helper.variable_expresser' => [
                'class' => \Mautic\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelper::class,
            ],
            'mautic.integrations.helper' => [
                'class'     => \Mautic\IntegrationsBundle\Helper\IntegrationsHelper::class,
                'arguments' => [
                    'mautic.plugin.integrations.repository.integration',
                    'mautic.integrations.service.encryption',
                    'event_dispatcher',
                ],
            ],
            'mautic.integrations.helper.auth_integrations' => [
                'class'     => \Mautic\IntegrationsBundle\Helper\AuthIntegrationsHelper::class,
                'arguments' => [
                    'mautic.integrations.helper',
                ],
            ],
            'mautic.integrations.helper.sync_integrations' => [
                'class'     => \Mautic\IntegrationsBundle\Helper\SyncIntegrationsHelper::class,
                'arguments' => [
                    'mautic.integrations.helper',
                    'mautic.integrations.internal.object_provider',
                ],
            ],
            'mautic.integrations.helper.config_integrations' => [
                'class'     => \Mautic\IntegrationsBundle\Helper\ConfigIntegrationsHelper::class,
                'arguments' => [
                    'mautic.integrations.helper',
                ],
            ],
            'mautic.integrations.helper.builder_integrations' => [
                'class'     => \Mautic\IntegrationsBundle\Helper\BuilderIntegrationsHelper::class,
                'arguments' => [
                    'mautic.integrations.helper',
                ],
            ],
            'mautic.integrations.helper.field_validator' => [
                'class'     => \Mautic\IntegrationsBundle\Helper\FieldValidationHelper::class,
                'arguments' => [
                    'mautic.integrations.sync.data_exchange.mautic.field_helper',
                    'translator',
                ],
            ],
        ],
        'other' => [
            'mautic.integrations.service.encryption' => [
                'class'     => \Mautic\IntegrationsBundle\Facade\EncryptionService::class,
                'arguments' => [
                    'mautic.helper.encryption',
                ],
            ],
            'mautic.integrations.internal.object_provider' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider::class,
                'arguments' => [
                    'event_dispatcher',
                ],
            ],
            'mautic.integrations.sync.notification.helper.owner_provider' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\Notification\Helper\OwnerProvider::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.integrations.internal.object_provider',
                ],
            ],
            'mautic.integrations.auth_provider.api_key' => [
                'class' => \Mautic\IntegrationsBundle\Auth\Provider\ApiKey\HttpFactory::class,
            ],
            'mautic.integrations.auth_provider.basic_auth' => [
                'class' => \Mautic\IntegrationsBundle\Auth\Provider\BasicAuth\HttpFactory::class,
            ],
            'mautic.integrations.auth_provider.oauth1atwolegged' => [
                'class' => \Mautic\IntegrationsBundle\Auth\Provider\Oauth1aTwoLegged\HttpFactory::class,
            ],
            'mautic.integrations.auth_provider.oauth2twolegged' => [
                'class' => \Mautic\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged\HttpFactory::class,
            ],
            'mautic.integrations.auth_provider.oauth2threelegged' => [
                'class' => \Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\HttpFactory::class,
            ],
            'mautic.integrations.auth_provider.token_persistence_factory' => [
                'class'     => \Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token\TokenPersistenceFactory::class,
                'arguments' => ['mautic.integrations.helper'],
            ],
            'mautic.integrations.token.parser' => [
                'class' => \Mautic\IntegrationsBundle\Helper\TokenParser::class,
            ],
        ],
        'repositories' => [
            'mautic.integrations.repository.field_change' => [
                'class'     => \Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\IntegrationsBundle\Entity\FieldChange::class,
                ],
            ],
            'mautic.integrations.repository.object_mapping' => [
                'class'     => \Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\IntegrationsBundle\Entity\ObjectMapping::class,
                ],
            ],
            // Placeholder till the plugin bundle implements this
            'mautic.plugin.integrations.repository.integration' => [
                'class'     => \Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \Mautic\PluginBundle\Entity\Integration::class,
                ],
            ],
        ],
        'sync' => [
            'mautic.sync.logger' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\Logger\DebugLogger::class,
                'arguments' => [
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.integrations.helper.sync_judge' => [
                'class' => \Mautic\IntegrationsBundle\Sync\SyncJudge\SyncJudge::class,
            ],
            'mautic.integrations.helper.contact_object' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.lead.repository.lead',
                    'doctrine.dbal.default_connection',
                    'mautic.lead.model.field',
                    'mautic.lead.model.dnc',
                    'mautic.lead.model.company',
                ],
            ],
            'mautic.integrations.helper.company_object' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\CompanyObjectHelper::class,
                'arguments' => [
                    'mautic.lead.model.company',
                    'mautic.lead.repository.company',
                    'doctrine.dbal.default_connection',
                ],
            ],
            'mautic.integrations.sync.data_exchange.mautic.order_executioner' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\OrderExecutioner::class,
                'arguments' => [
                    'mautic.integrations.helper.sync_mapping',
                    'event_dispatcher',
                    'mautic.integrations.internal.object_provider',
                ],
            ],
            'mautic.integrations.sync.data_exchange.mautic.field_helper' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper::class,
                'arguments' => [
                    'mautic.lead.model.field',
                    'mautic.integrations.helper.variable_expresser',
                    'mautic.channel.helper.channel_list',
                    'translator',
                    'event_dispatcher',
                    'mautic.integrations.internal.object_provider',
                ],
            ],
            'mautic.integrations.sync.sync_process.value_helper' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Helper\ValueHelper::class,
                'arguments' => [],
            ],
            'mautic.integrations.sync.data_exchange.mautic.field_builder' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\FieldBuilder::class,
                'arguments' => [
                    'router',
                    'mautic.integrations.sync.data_exchange.mautic.field_helper',
                    'mautic.integrations.helper.contact_object',
                ],
            ],
            'mautic.integrations.sync.data_exchange.mautic.full_object_report_builder' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\FullObjectReportBuilder::class,
                'arguments' => [
                    'mautic.integrations.sync.data_exchange.mautic.field_builder',
                    'mautic.integrations.internal.object_provider',
                    'event_dispatcher',
                ],
            ],
            'mautic.integrations.sync.data_exchange.mautic.partial_object_report_builder' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\PartialObjectReportBuilder::class,
                'arguments' => [
                    'mautic.integrations.repository.field_change',
                    'mautic.integrations.sync.data_exchange.mautic.field_helper',
                    'mautic.integrations.sync.data_exchange.mautic.field_builder',
                    'mautic.integrations.internal.object_provider',
                    'event_dispatcher',
                ],
            ],
            'mautic.integrations.sync.data_exchange.mautic' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange::class,
                'arguments' => [
                    'mautic.integrations.repository.field_change',
                    'mautic.integrations.sync.data_exchange.mautic.field_helper',
                    'mautic.integrations.helper.sync_mapping',
                    'mautic.integrations.sync.data_exchange.mautic.full_object_report_builder',
                    'mautic.integrations.sync.data_exchange.mautic.partial_object_report_builder',
                    'mautic.integrations.sync.data_exchange.mautic.order_executioner',
                ],
            ],
            'mautic.integrations.sync.integration_process.object_change_generator' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Integration\ObjectChangeGenerator::class,
                'arguments' => [
                    'mautic.integrations.sync.sync_process.value_helper',
                ],
            ],
            'mautic.integrations.sync.integration_process' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Integration\IntegrationSyncProcess::class,
                'arguments' => [
                    'mautic.integrations.helper.sync_date',
                    'mautic.integrations.helper.sync_mapping',
                    'mautic.integrations.sync.integration_process.object_change_generator',
                ],
            ],
            'mautic.integrations.sync.internal_process.object_change_generator' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\ObjectChangeGenerator::class,
                'arguments' => [
                    'mautic.integrations.helper.sync_judge',
                    'mautic.integrations.sync.sync_process.value_helper',
                    'mautic.integrations.sync.data_exchange.mautic.field_helper',
                ],
            ],
            'mautic.integrations.sync.internal_process' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\MauticSyncProcess::class,
                'arguments' => [
                    'mautic.integrations.helper.sync_date',
                    'mautic.integrations.sync.internal_process.object_change_generator',
                ],
            ],
            'mautic.integrations.sync.service' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\SyncService\SyncService::class,
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
            'mautic.integrations.helper.sync_date' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\Helper\SyncDateHelper::class,
                'arguments' => [
                    'doctrine.dbal.default_connection',
                ],
            ],
            'mautic.integrations.helper.sync_mapping' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\Helper\MappingHelper::class,
                'arguments' => [
                    'mautic.lead.model.field',
                    'mautic.integrations.repository.object_mapping',
                    'mautic.integrations.internal.object_provider',
                    'event_dispatcher',
                ],
            ],
            'mautic.integrations.sync.helper.relations' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\Helper\RelationsHelper::class,
                'arguments' => [
                    'mautic.integrations.helper.sync_mapping',
                ],
            ],
            'mautic.integrations.sync.notifier' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\Notification\Notifier::class,
                'arguments' => [
                    'mautic.integrations.sync.notification.handler_container',
                    'mautic.integrations.helper.sync_integrations',
                    'mautic.integrations.helper.config_integrations',
                ],
            ],
            'mautic.integrations.sync.notification.writer' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\Notification\Writer::class,
                'arguments' => [
                    'mautic.core.model.notification',
                    'mautic.core.model.auditlog',
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic.integrations.sync.notification.handler_container' => [
                'class' => \Mautic\IntegrationsBundle\Sync\Notification\Handler\HandlerContainer::class,
            ],
            'mautic.integrations.sync.notification.handler_company' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\Notification\Handler\CompanyNotificationHandler::class,
                'arguments' => [
                    'mautic.integrations.sync.notification.writer',
                    'mautic.integrations.sync.notification.helper_user_notification',
                    'mautic.integrations.sync.notification.helper_company',
                ],
                'tag' => 'mautic.sync.notification_handler',
            ],
            'mautic.integrations.sync.notification.handler_contact' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\Notification\Handler\ContactNotificationHandler::class,
                'arguments' => [
                    'mautic.integrations.sync.notification.writer',
                    'mautic.lead.repository.lead_event_log',
                    'doctrine.orm.entity_manager',
                    'mautic.integrations.sync.notification.helper_user_summary_notification',
                ],
                'tag' => 'mautic.sync.notification_handler',
            ],
            'mautic.integrations.sync.notification.helper_company' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\Notification\Helper\CompanyHelper::class,
                'arguments' => [
                    'doctrine.dbal.default_connection',
                ],
            ],
            'mautic.integrations.sync.notification.helper_user' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\Notification\Helper\UserHelper::class,
                'arguments' => [
                    'doctrine.dbal.default_connection',
                ],
            ],
            'mautic.integrations.sync.notification.helper_route' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\Notification\Helper\RouteHelper::class,
                'arguments' => [
                    'mautic.integrations.internal.object_provider',
                    'event_dispatcher',
                ],
            ],
            'mautic.integrations.sync.notification.helper_user_notification' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\Notification\Helper\UserNotificationHelper::class,
                'arguments' => [
                    'mautic.integrations.sync.notification.writer',
                    'mautic.integrations.sync.notification.helper_user',
                    'mautic.integrations.sync.notification.helper.owner_provider',
                    'mautic.integrations.sync.notification.helper_route',
                    'translator',
                ],
            ],
            'mautic.integrations.sync.notification.helper_user_summary_notification' => [
                'class'     => \Mautic\IntegrationsBundle\Sync\Notification\Helper\UserSummaryNotificationHelper::class,
                'arguments' => [
                    'mautic.integrations.sync.notification.writer',
                    'mautic.integrations.sync.notification.helper_user',
                    'mautic.integrations.sync.notification.helper.owner_provider',
                    'mautic.integrations.sync.notification.helper_route',
                    'translator',
                ],
            ],
        ],
    ],
];
