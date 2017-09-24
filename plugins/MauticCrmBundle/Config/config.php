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
                'class'     => 'MauticPlugin\MauticCrmBundle\EventListener\LeadSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic_integration.pipedrive.export.lead',
                ],
            ],
            'mautic_integration.pipedrive.company.subscriber' => [
                'class'     => 'MauticPlugin\MauticCrmBundle\EventListener\CompanySubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic_integration.pipedrive.export.company',
                ],
            ],
            'mautic.integration.leadbundle.subscriber' => [
                'class'     => 'MauticPlugin\MauticCrmBundle\EventListener\LeadListSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.lead.model.list',
                ],
            ],
        ],
        'integrations' => [
            'mautic.integration.hubspot' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration::class,
                'arguments' => [
                    'mautic.helper.user',
                ],
            ],
            'mautic.integration.salesforce' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration::class,
                'arguments' => [
                ],
            ],
            'mautic.integration.sugarcrm' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\SugarcrmIntegration::class,
                'arguments' => [
                ],
            ],
            'mautic.integration.vtiger' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\VtigerIntegration::class,
                'arguments' => [
                ],
            ],
            'mautic.integration.zoho' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\ZohoIntegration::class,
                'arguments' => [
                ],
            ],
            'mautic.integration.dynamics' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\DynamicsIntegration::class,
                'arguments' => [
                ],
            ],
            'mautic.integration.connectwise' => [
                'class'     => \MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration::class,
                'arguments' => [
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
                'class'     => 'MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import\OwnerImport',
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic_integration.pipedrive.import.company' => [
                'class'     => 'MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import\CompanyImport',
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic_integration.pipedrive.import.lead' => [
                'class'     => 'MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Import\LeadImport',
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic_integration.pipedrive.export.company' => [
                'class'     => 'MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export\CompanyExport',
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
            'mautic_integration.pipedrive.export.lead' => [
                'class'     => 'MauticPlugin\MauticCrmBundle\Integration\Pipedrive\Export\LeadExport',
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.connectwise.campaignaction' => [
                'class' => MauticPlugin\MauticCrmBundle\Form\Type\IntegrationCampaignsTaskType::class,
                'alias' => 'integration_campaign_task',
            ],
        ],
    ],
];
