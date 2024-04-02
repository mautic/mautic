<?php

return [
    'routes' => [
        'main' => [
            'mautic_integration_auth_callback_secure' => [
                'path'       => '/plugins/integrations/authcallback/{integration}',
                'controller' => 'Mautic\PluginBundle\Controller\AuthController::authCallbackAction',
            ],
            'mautic_integration_auth_postauth_secure' => [
                'path'       => '/plugins/integrations/authstatus/{integration}',
                'controller' => 'Mautic\PluginBundle\Controller\AuthController::authStatusAction',
            ],
            'mautic_plugin_index' => [
                'path'       => '/plugins',
                'controller' => 'Mautic\PluginBundle\Controller\PluginController::indexAction',
            ],
            'mautic_plugin_config' => [
                'path'       => '/plugins/config/{name}/{page}',
                'controller' => 'Mautic\PluginBundle\Controller\PluginController::configAction',
            ],
            'mautic_plugin_info' => [
                'path'       => '/plugins/info/{name}',
                'controller' => 'Mautic\PluginBundle\Controller\PluginController::infoAction',
            ],
            'mautic_plugin_reload' => [
                'path'       => '/plugins/reload',
                'controller' => 'Mautic\PluginBundle\Controller\PluginController::reloadAction',
            ],
        ],
        'public' => [
            'mautic_integration_auth_user' => [
                'path'       => '/plugins/integrations/authuser/{integration}',
                'controller' => 'Mautic\PluginBundle\Controller\AuthController::authUserAction',
            ],
            'mautic_integration_auth_callback' => [
                'path'       => '/plugins/integrations/authcallback/{integration}',
                'controller' => 'Mautic\PluginBundle\Controller\AuthController::authCallbackAction',
            ],
            'mautic_integration_auth_postauth' => [
                'path'       => '/plugins/integrations/authstatus/{integration}',
                'controller' => 'Mautic\PluginBundle\Controller\AuthController::authStatusAction',
            ],
        ],
    ],
    'menu' => [
        'admin' => [
            'priority' => 50,
            'items'    => [
                'mautic.plugin.plugins' => [
                    'id'        => 'mautic_plugin_root',
                    'iconClass' => 'fa-plus-circle',
                    'access'    => 'plugin:plugins:manage',
                    'route'     => 'mautic_plugin_index',
                ],
            ],
        ],
    ],

    'services' => [
        'other' => [
            'mautic.helper.integration' => [
                'class'     => \Mautic\PluginBundle\Helper\IntegrationHelper::class,
                'arguments' => [
                    'service_container',
                    'doctrine.orm.entity_manager',
                    'mautic.helper.paths',
                    'mautic.helper.bundle',
                    'mautic.helper.core_parameters',
                    'twig',
                    'mautic.plugin.model.plugin',
                ],
            ],
            'mautic.plugin.helper.reload' => [
                'class'     => \Mautic\PluginBundle\Helper\ReloadHelper::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.factory',
                ],
            ],
        ],
        'facades' => [
            'mautic.plugin.facade.reload' => [
                'class'     => \Mautic\PluginBundle\Facade\ReloadFacade::class,
                'arguments' => [
                    'mautic.plugin.model.plugin',
                    'mautic.plugin.helper.reload',
                    'translator',
                ],
            ],
        ],
    ],
];
