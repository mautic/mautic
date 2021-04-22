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
    'name'        => 'CRM',
    'description' => 'Enables integration with Mautic supported CRMs.',
    'version'     => '1.0',
    'author'      => 'Mautic',
    'routes'      => [
        'public' => [
            'mautic_integration_contacts' => [
                'path'         => '/plugin/{integration}/contact_data',
                'controller'   => 'MauticCrmBundle:Public:contactData',
                'requirements' => [
                    'integration' => '.+',
                ],
            ],
            'mautic_integration_companies' => [
                'path'         => '/plugin/{integration}/company_data',
                'controller'   => 'MauticCrmBundle:Public:companyData',
                'requirements' => [
                    'integration' => '.+',
                ],
            ],
            'mautic_integration.pipedrive.webhook' => [
                'path'       => '/plugin/pipedrive/webhook',
                'controller' => 'MauticCrmBundle:Pipedrive:webhook',
                'method'     => 'POST',
            ],
        ],
    ],
    'services' => [
        'events' => [
            'mautic_integration.pipedrive.lead.subscriber' => [
                'class'     => \MauticPlugin\MauticCrmBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic_integration.pipedrive.export.lead',
                ],
            ],
            'mautic_integration.pipedrive.company.subscriber' => [
                'class'     => \MauticPlugin\MauticCrmBundle\EventListener\CompanySubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic_integration.pipedrive.export.company',
                ],
            ],
            'mautic.integration.leadbundle.subscriber' => [
                'class'     => \MauticPlugin\MauticCrmBundle\EventListener\LeadListSubscriber::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.lead.model.list',
                    'translator',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.hubspot' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration::class,
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
                    'mautic.helper.user',
                ],
            ],
            'mautic.integration.salesforce' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration::class,
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
            'mautic.integration.sugarcrm' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\SugarcrmIntegration::class,
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
                    'mautic.user.model.user',
                ],
            ],
            'mautic.integration.vtiger' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\VtigerIntegration::class,
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
            'mautic.integration.zoho' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\ZohoIntegration::class,
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
            'mautic.integration.dynamics' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\DynamicsIntegration::class,
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
            'mautic.integration.connectwise' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration::class,
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
            'mautic.integration.pipedrive' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration::class,
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
                    'mautic_integration.service.transport',
                    'mautic_integration.pipedrive.export.lead',
                ],
            ],
        ],
        'other' => [
            'mautic_integration.pipedrive.guzzle.client' => [
                'class' => 'GuzzleHttp\Client',
            ],
            'mautic_integration.service.transport' => [
                'class'     => 'MauticPlugin\MauticCrmBundle\Services\Transport',
                'arguments' => [
                    'mautic_integration.pipedrive.guzzle.client',
                ],
            ],
            'mautic_integration.pipedrive.import.owner' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import\OwnerImport::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic_integration.pipedrive.import.company' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import\CompanyImport::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.lead.model.company',
                ],
            ],
            'mautic_integration.pipedrive.import.lead' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import\LeadImport::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                ],
            ],
            'mautic_integration.pipedrive.export.company' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export\CompanyExport::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic_integration.pipedrive.export.lead' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export\LeadExport::class,
                'arguments' => [
                    'doctrine.orm.entity_manager',
                    'mautic_integration.pipedrive.export.company',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.connectwise.campaignaction' => [
                'class'     => MauticPlugin\MauticCrmBundle\Form\Type\IntegrationCampaignsTaskType::class,
                'arguments' => ['mautic.integration.connectwise'],
            ],
        ],
        'commands' => [
            'mautic_integration.pipedrive.data_fetch' => [
                'tag'       => 'console.command',
                'class'     => MauticPlugin\MauticCrmBundle\Command\FetchPipedriveDataCommand::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'templating.helper.translator',
                ],
            ],
            'mautic_integration.pipedrive.data_push' => [
                'tag'       => 'console.command',
                'class'     => MauticPlugin\MauticCrmBundle\Command\PushDataToPipedriveCommand::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'templating.helper.translator',
                    'doctrine.orm.entity_manager',
                    'mautic_integration.pipedrive.export.company',
                    'mautic_integration.pipedrive.export.lead',
                ],
            ],
        ],
    ],
];
