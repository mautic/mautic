<?php

declare(strict_types=1);

return [
    'name'        => 'GrapesJS Builder',
    'description' => 'GrapesJS Builder with MJML support for Mautic',
    'version'     => '1.0.0',
    'author'      => 'Mautic Community',
    'routes'      => [
        'main'   => [
            'grapesjsbuilder_upload' => [
                'path'       => '/grapesjsbuilder/upload',
                'controller' => 'GrapesJsBuilderBundle:FileManager:upload',
            ],
            'grapesjsbuilder_delete' => [
                'path'       => '/grapesjsbuilder/delete',
                'controller' => 'GrapesJsBuilderBundle:FileManager:delete',
            ],
            'grapesjsbuilder_builder' => [
                'path'       => '/grapesjsbuilder/{objectType}/{objectId}',
                'controller' => 'GrapesJsBuilderBundle:GrapesJs:builder',
            ],
        ],
        'public' => [],
        'api'    => [],
    ],
    'menu'        => [],
    'services'    => [
        'other'        => [
            // Provides access to configured API keys, settings, field mapping, etc
            'grapesjsbuilder.config' => [
                'class'     => \MauticPlugin\GrapesJsBuilderBundle\Integration\Config::class,
                'arguments' => [
                    'mautic.integrations.helper',
                ],
            ],
        ],
        'sync'         => [],
        'integrations' => [
            // Basic definitions with name, display name and icon
            'mautic.integration.grapesjsbuilder' => [
                'class' => \MauticPlugin\GrapesJsBuilderBundle\Integration\GrapesJsBuilderIntegration::class,
                'tags'  => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            // Provides the form types to use for the configuration UI
            'grapesjsbuilder.integration.configuration' => [
                'class'     => \MauticPlugin\GrapesJsBuilderBundle\Integration\Support\ConfigSupport::class,
                'tags'      => [
                    'mautic.config_integration',
                ],
            ],
            // Tells Mautic what themes it should support when enabled
            'grapesjsbuilder.integration.builder' => [
                'class'     => \MauticPlugin\GrapesJsBuilderBundle\Integration\Support\BuilderSupport::class,
                'tags'      => [
                    'mautic.builder_integration',
                ],
            ],
        ],
        'models'  => [
            'grapesjsbuilder.model' => [
                'class'     => \MauticPlugin\GrapesJsBuilderBundle\Model\GrapesJsBuilderModel::class,
                'arguments' => [
                    'request_stack',
                    'mautic.email.model.email',
                ],
            ],
        ],
        'helpers' => [
            'grapesjsbuilder.helper.filemanager' => [
                'class'     => \MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager::class,
                'arguments' => [
                    'mautic.helper.file_uploader',
                    'mautic.helper.core_parameters',
                    'mautic.helper.paths',
                ],
            ],
        ],
        'events'  => [
            'grapesjsbuilder.event.assets.subscriber' => [
                'class'     => \MauticPlugin\GrapesJsBuilderBundle\EventSubscriber\AssetsSubscriber::class,
                'arguments' => [
                    'grapesjsbuilder.config',
                    'mautic.install.service',
                ],
            ],
            'grapesjsbuilder.event.email.subscriber' => [
                'class'     => \MauticPlugin\GrapesJsBuilderBundle\EventSubscriber\EmailSubscriber::class,
                'arguments' => [
                    'grapesjsbuilder.config',
                    'grapesjsbuilder.model',
                ],
            ],
            'grapesjsbuilder.event.content.subscriber' => [
                'class'     => \MauticPlugin\GrapesJsBuilderBundle\EventSubscriber\InjectCustomContentSubscriber::class,
                'arguments' => [
                    'grapesjsbuilder.config',
                    'grapesjsbuilder.model',
                    'grapesjsbuilder.helper.filemanager',
                    'mautic.helper.templating',
                    'request_stack',
                    'router',
                ],
            ],
        ],
    ],
    'parameters' => [
        'image_path_exclude'     => ['flags', 'mejs'], // exclude certain folders from showing in the image browser
        'static_url'             => '', // optional base url for images
    ],
];
