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
            'mautic_integration_core' => [
                'path' => '/integration',
                'controller' => 'MauticIntegrationBundle:Default:index',
            ],
        ],
        'public' => [
        ],
        'api' => [
            'mautic_integration_api_plugin_list' => [
                'path' => '/integration/plugin/list',
                'controller' => 'MauticIntegrationsBundle:Api\Plugin:list',
            ],
        ],
    ],
    'menu' => [
    ],
    'services' => [
        'events' => [
            'mautic.integrations.lead.subscriber' => [
                'class'     => \MauticPlugin\MauticIntegrationsBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.integrations.repository.field_change',
                    'mautic.integrations.helper.variable_expressor'
                ],
            ],
        ],
        'forms' => [
        ],
        'helpers' => [
            'mautic.integrations.helper.variable_expressor' => [
                'class' => \MauticPlugin\MauticIntegrationsBundle\Helpers\VariableExpressor\VariableExpressorHelper::class
            ]
        ],
        'menus' => [
        ],
        'other' => [
            'mautic.service.encryption' => [
                'class' => \MauticPlugin\MauticIntegrationsBundle\Facade\EncryptionService::class,
                'arguments' => [
                    'mautic.helper.encryption',
                ],
            ],
            'mautic.integrations.service.sync' => [
                'class' => \MauticPlugin\MauticIntegrationsBundle\Services\SyncService\SyncService::class,
                'arguments' => [
                    'mautic.plugin.repository.integration_entity',
                ]
            ]
        ],
        'models' => [
        ],
        'validator' => [
        ],
        'repositories' => [
            'mautic.integrations.repository.field_change' => [
                'class'     => \Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => [
                    \MauticPlugin\MauticIntegrationsBundle\Entity\FieldChangeRepository::class,
                ],
            ],
        ],
    ],

    'parameters' => [
        'plugin_dir' => '%kernel.root_dir%/../plugins',
    ],
];
