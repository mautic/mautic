<?php

declare(strict_types=1);

use MauticPlugin\GrapesJsBuilderBundle\Controller\FileManagerController;
use MauticPlugin\GrapesJsBuilderBundle\Controller\GrapesJsController;

return [
    'name'        => 'GrapesJS Builder',
    'description' => 'GrapesJS Builder with MJML support for Mautic',
    'version'     => '1.0.0',
    'author'      => 'Mautic Community',
    'routes'      => [
        'main'   => [
            'grapesjsbuilder_upload' => [
                'path'       => '/grapesjsbuilder/upload',
                'controller' => FileManagerController::class.'::uploadAction',
            ],
            'grapesjsbuilder_delete' => [
                'path'       => '/grapesjsbuilder/delete',
                'controller' => FileManagerController::class.'::deleteAction',
            ],
            'grapesjsbuilder_media' => [
                'path'       => '/grapesjsbuilder/media',
                'controller' => FileManagerController::class.'::getMediaAction',
            ],
            'grapesjsbuilder_builder' => [
                'path'       => '/grapesjsbuilder/{objectType}/{objectId}',
                'controller' => GrapesJsController::class.'::builderAction',
            ],
            'grapesjsbuilder_editor_state' => [
                'path'       => '/grapesjsbuilder/{objectType}/{objectId}/editor-state',
                'controller' => GrapesJsController::class.'::editorStateAction',
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
