<?php

return [
    'routes' => [
        'main' => [
            'mautic_integration_auth_callback_secure' => [
                'path'       => '/plugins/integrations/authcallback/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authCallback',
            ],
            'mautic_integration_auth_postauth_secure' => [
                'path'       => '/plugins/integrations/authstatus/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authStatus',
            ],
            'mautic_plugin_index' => [
                'path'       => '/plugins',
                'controller' => 'MauticPluginBundle:Plugin:index',
            ],
            'mautic_plugin_config' => [
                'path'       => '/plugins/config/{name}/{page}',
                'controller' => 'MauticPluginBundle:Plugin:config',
            ],
            'mautic_plugin_info' => [
                'path'       => '/plugins/info/{name}',
                'controller' => 'MauticPluginBundle:Plugin:info',
            ],
            'mautic_plugin_reload' => [
                'path'       => '/plugins/reload',
                'controller' => 'MauticPluginBundle:Plugin:reload',
            ],
        ],
        'public' => [
            'mautic_integration_auth_user' => [
                'path'       => '/plugins/integrations/authuser/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authUser',
            ],
            'mautic_integration_auth_callback' => [
                'path'       => '/plugins/integrations/authcallback/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authCallback',
            ],
            'mautic_integration_auth_postauth' => [
                'path'       => '/plugins/integrations/authstatus/{integration}',
                'controller' => 'MauticPluginBundle:Auth:authStatus',
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
        'events' => [
            'mautic.plugin.pointbundle.subscriber' => [
                'class' => \Mautic\PluginBundle\EventListener\PointSubscriber::class,
            ],
            'mautic.plugin.formbundle.subscriber' => [
                'class'       => \Mautic\PluginBundle\EventListener\FormSubscriber::class,
                'methodCalls' => [
                    'setIntegrationHelper' => [
                        'mautic.helper.integration',
                    ],
                ],
            ],
            'mautic.plugin.campaignbundle.subscriber' => [
                'class'       => \Mautic\PluginBundle\EventListener\CampaignSubscriber::class,
                'methodCalls' => [
                    'setIntegrationHelper' => [
                        'mautic.helper.integration',
                    ],
                ],
            ],
            'mautic.plugin.leadbundle.subscriber' => [
                'class'     => \Mautic\PluginBundle\EventListener\LeadSubscriber::class,
                'arguments' => [
                    'mautic.plugin.model.plugin',
                ],
            ],
            'mautic.plugin.integration.subscriber' => [
                'class'     => \Mautic\PluginBundle\EventListener\IntegrationSubscriber::class,
                'arguments' => [
                    'monolog.logger.mautic',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.integration.details' => [
                'class' => \Mautic\PluginBundle\Form\Type\DetailsType::class,
            ],
            'mautic.form.type.integration.settings' => [
                'class'     => \Mautic\PluginBundle\Form\Type\FeatureSettingsType::class,
                'arguments' => [
                    'session',
                    'mautic.helper.core_parameters',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.form.type.integration.fields' => [
                'class'     => \Mautic\PluginBundle\Form\Type\FieldsType::class,
            ],
            'mautic.form.type.integration.company.fields' => [
                'class'     => \Mautic\PluginBundle\Form\Type\CompanyFieldsType::class,
            ],
            'mautic.form.type.integration.keys' => [
                'class' => \Mautic\PluginBundle\Form\Type\KeysType::class,
            ],
            'mautic.form.type.integration.list' => [
                'class'     => \Mautic\PluginBundle\Form\Type\IntegrationsListType::class,
                'arguments' => [
                    'mautic.helper.integration',
                ],
            ],
            'mautic.form.type.integration.config' => [
                'class' => \Mautic\PluginBundle\Form\Type\IntegrationConfigType::class,
            ],
            'mautic.form.type.integration.campaign' => [
                'class' => \Mautic\PluginBundle\Form\Type\IntegrationCampaignsType::class,
            ],
        ],
        'other' => [
            'mautic.helper.integration' => [
                'class'     => \Mautic\PluginBundle\Helper\IntegrationHelper::class,
                'arguments' => [
                    'service_container',
                    'doctrine.orm.entity_manager',
                    'mautic.helper.paths',
                    'mautic.helper.bundle',
                    'mautic.helper.core_parameters',
                    'mautic.helper.templating',
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
        'models' => [
            'mautic.plugin.model.plugin' => [
                'class'     => \Mautic\PluginBundle\Model\PluginModel::class,
                'arguments' => [
                    'mautic.lead.model.field',
                    'mautic.helper.core_parameters',
                    'mautic.helper.bundle',
                ],
            ],

            'mautic.plugin.model.integration_entity' => [
                'class' => Mautic\PluginBundle\Model\IntegrationEntityModel::class,
            ],
        ],
    ],
];
