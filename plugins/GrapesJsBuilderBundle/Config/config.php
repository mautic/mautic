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
            'grapesjsbuilder_media' => [
                'path'       => '/grapesjsbuilder/media',
                'controller' => 'MauticPlugin\GrapesJsBuilderBundle\Controller\FileManagerController::getMediaAction',
            ],
            'grapesjsbuilder_builder' => [
                'path'       => '/grapesjsbuilder/{objectType}/{objectId}',
                'controller' => 'MauticPlugin\GrapesJsBuilderBundle\Controller\GrapesJsController::builderAction',
            ],
            'grapesjsbuilder_editor_state' => [
                'path'       => '/grapesjsbuilder/{objectType}/{objectId}/editor-state',
                'controller' => 'MauticPlugin\GrapesJsBuilderBundle\Controller\GrapesJsController::editorStateAction',
                'methods'    => ['GET'],
            ],
        ],
        'public' => [],
        'api'    => [],
    ],
    'menu'        => [],
    'parameters' => [
        'image_path_exclude'     => ['flags', 'mejs'], // exclude certain folders from showing in the image browser
        'static_url'             => '', // optional base url for images
    ],
];
