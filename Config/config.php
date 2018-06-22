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
                'controller' => 'MauticIntegrationBundle:Api\Plugin:list',
            ],
        ],
    ],
    'menu' => [
    ],
    'services' => [
        'events' => [
        ],
        'forms' => [
        ],
        'helpers' => [
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
        ],
        'models' => [
        ],
        'validator' => [
        ],
    ],

    'parameters' => [
        'plugin_dir' => '%kernel.root_dir%/../plugins',
    ],
];
