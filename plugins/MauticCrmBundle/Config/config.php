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
                'class'     => 'MauticPlugin\MauticCrmBundle\EventListener\LeadListSubscriber',
                'arguments' => [
                    'mautic.helper.integration',
                    'mautic.lead.model.list',
                ],
            ],
        ],
    ],
];
