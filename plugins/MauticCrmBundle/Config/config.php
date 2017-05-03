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

        /** Page : INES CRM sync log **/
		'main' => [
			'ines_logs' => [
				'path' => '/ines/logs',
				'controller' => 'MauticCrmBundle:Ines:logs'
			]
		],

		/** Adding an End-Point to the Mautic API to retrieve INES plugin mapping and config **/
		'api' => [
			'plugin_crm_bundle_ines_get_mapping_api' => [
				'path' => '/ines/getMapping',
				'controller' => 'MauticCrmBundle:Api:inesGetMapping',
				'method' => 'GET'
			]
		],
    ],
    'services' => [
		'events' => [
            'mautic.crm.leadbundle.subscriber' => [
                'class' => 'MauticPlugin\MauticCrmBundle\EventListener\LeadSubscriber'
            ]
		],
		'models' =>  [
            'mautic.crm.model.ines_sync_log' => [
                'class' => 'MauticPlugin\MauticCrmBundle\Model\InesSyncLogModel',
				'arguments' => ['mautic.factory']
            ]
		]
	]
];
