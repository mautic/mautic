<?php

declare(strict_types=1);

use MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use MauticPlugin\GrapesJsBuilderBundle\Integration\GrapesJsBuilderIntegration;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Support\BuilderSupport;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Support\ConfigSupport;

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
                'class'     => Config::class,
                'arguments' => [
                    'mautic.integrations.helper',
                ],
            ],
        ],
        'sync'         => [],
        'integrations' => [
            // Basic definitions with name, display name and icon
            'mautic.integration.grapesjsbuilder' => [
                'class' => GrapesJsBuilderIntegration::class,
                'tags'  => [
                    'mautic.integration',
                    'mautic.basic_integration',
                ],
            ],
            // Provides the form types to use for the configuration UI
            'grapesjsbuilder.integration.configuration' => [
                'class'     => ConfigSupport::class,
                'tags'      => [
                    'mautic.config_integration',
                ],
            ],
            // Tells Mautic what themes it should support when enabled
            'grapesjsbuilder.integration.builder' => [
                'class'     => BuilderSupport::class,
                'tags'      => [
                    'mautic.builder_integration',
                ],
            ],
        ],
        'helpers' => [
            'grapesjsbuilder.helper.filemanager' => [
                'class'     => FileManager::class,
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
