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
            /** @depreacated since Mautic 5.2, to be removed in 6.0. Use grapesjsbuilder_media instead */
            'grapesjsbuilder_assets' => [
                'path'       => '/grapesjsbuilder/assets',
                'controller' => 'MauticPlugin\GrapesJsBuilderBundle\Controller\FileManagerController::assetsAction',
            ],
            'grapesjsbuilder_media' => [
                'path'       => '/grapesjsbuilder/media',
                'controller' => 'MauticPlugin\GrapesJsBuilderBundle\Controller\FileManagerController::getMediaAction',
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
                'class'     => MauticPlugin\GrapesJsBuilderBundle\Integration\Config::class,
                'arguments' => [
                    'mautic.integrations.helper',
                ],
            ],
        ],
        'sync'         => [],
        'helpers'      => [
            'grapesjsbuilder.helper.filemanager' => [
                'class'     => MauticPlugin\GrapesJsBuilderBundle\Helper\FileManager::class,
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
