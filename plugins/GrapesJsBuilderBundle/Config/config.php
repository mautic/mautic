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
                'controller' => 'MauticPlugin\GrapesJsBuilderBundle\Controller\FileManagerController::uploadAction',
            ],
            'grapesjsbuilder_delete' => [
                'path'       => '/grapesjsbuilder/delete',
                'controller' => 'MauticPlugin\GrapesJsBuilderBundle\Controller\FileManagerController::deleteAction',
            ],
            'grapesjsbuilder_assets' => [
                'path'       => '/grapesjsbuilder/assets',
                'controller' => 'MauticPlugin\GrapesJsBuilderBundle\Controller\FileManagerController::assetsAction',
            ],
            'grapesjsbuilder_builder' => [
                'path'       => '/grapesjsbuilder/{objectType}/{objectId}',
                'controller' => 'MauticPlugin\GrapesJsBuilderBundle\Controller\GrapesJsController::builderAction',
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
    ],
    'parameters' => [
        'image_path_exclude'     => ['flags', 'mejs'], // exclude certain folders from showing in the image browser
        'static_url'             => '', // optional base url for images
    ],
];
