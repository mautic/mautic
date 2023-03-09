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
        ],
    ],
    'services' => [
        'events' => [
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
        ],
        'forms' => [
            'mautic.form.type.connectwise.campaignaction' => [
                'class'     => MauticPlugin\MauticCrmBundle\Form\Type\IntegrationCampaignsTaskType::class,
                'arguments' => ['mautic.integration.connectwise'],
            ],
        ],
    ],
];
