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
        'commands' => [
            'mautic.integrations.command.sync' => [
                'class'     => \MauticPlugin\MauticIntegrationsBundle\Command\SyncCommand::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.integrations.sync.service',
                ],
                'tag' => 'console.command',
            ],
        ],
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
                'class' => \MauticPlugin\MauticIntegrationsBundle\Helpers\VariableExpressor\VariableExpresserHelper::class
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
            'mautic.http.client' => [
                'class' => GuzzleHttp\Client::class
            ],
            'mautic.integrations.auth.factory' => [
                'class' => MauticPlugin\MauticIntegrationsBundle\Auth\Factory::class,
            ],
            'mautic.integrations.auth.provider.oauth1a' => [
                'class' => MauticPlugin\MauticIntegrationsBundle\Auth\Provider\OAuth1aProvider::class,
                'arguments' => [
                    'mautic.http.client',
                ],
                'tag' => 'mautic.integrations.auth.provider'
            ],
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
                    \MauticPlugin\MauticIntegrationsBundle\Entity\FieldChange::class,
                ],
            ],
        ],
        'sync' => [
            'mautic.integrations.helper.sync_judge' => [
                'class' => \MauticPlugin\MauticIntegrationsBundle\Helpers\SyncJudge\SyncJudge::class,
            ],
            'mautic.integrations.sync.data_exchange.mautic' => [
                'class' => \MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchange\MauticSyncDataExchange::class,
                'arguments' => [
                    // @todo add mautic sync arguments
                ],
            ],
            'mautic.integrations.helper.sync_process_factory' => [
                'class' => \MauticPlugin\MauticIntegrationsBundle\Helpers\SyncProcess\SyncProcessFactory::class,
            ],
            'mautic.integrations.sync.service' => [
                'class' => \MauticPlugin\MauticIntegrationsBundle\Services\SyncService\SyncService::class,
                'arguments' => [
                    'mautic.integrations.helper.sync_process_factory',
                    'mautic.integrations.helper.sync_judge',
                    'mautic.integrations.sync.data_exchange.mautic',
                ],
            ],
        ],
    ],

    'parameters' => [
        'plugin_dir' => '%kernel.root_dir%/../plugins',
    ],
];
