<?php

return [
    'routes' => [
        'main' => [
            'mautic_asset_index' => [
                'path'       => '/assets/{page}',
                'controller' => 'Mautic\AssetBundle\Controller\AssetController::indexAction',
            ],
            'mautic_asset_remote' => [
                'path'       => '/assets/remote',
                'controller' => 'Mautic\AssetBundle\Controller\AssetController::remoteAction',
            ],
            'mautic_asset_action' => [
                'path'       => '/assets/{objectAction}/{objectId}',
                'controller' => 'Mautic\AssetBundle\Controller\AssetController::executeAction',
            ],
        ],
        'api' => [
            'mautic_api_assetsstandard' => [
                'standard_entity' => true,
                'name'            => 'assets',
                'path'            => '/assets',
                'controller'      => Mautic\AssetBundle\Controller\Api\AssetApiController::class,
            ],
        ],
        'public' => [
            'mautic_asset_download' => [
                'path'       => '/asset/{slug}',
                'controller' => 'Mautic\AssetBundle\Controller\PublicController::downloadAction',
                'defaults'   => [
                    'slug' => '',
                ],
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'items' => [
                'mautic.asset.assets' => [
                    'route'    => 'mautic_asset_index',
                    'access'   => ['asset:assets:viewown', 'asset:assets:viewother'],
                    'parent'   => 'mautic.core.components',
                    'priority' => 300,
                ],
            ],
        ],
    ],

    'categories' => [
        'asset' => [
            'class' => Mautic\AssetBundle\Entity\Asset::class,
        ],
    ],

    'services' => [
        'permissions' => [
            'mautic.asset.permissions' => [
                'class'     => Mautic\AssetBundle\Security\Permissions\AssetPermissions::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
        'others' => [
            'mautic.asset.upload.error.handler' => [
                'class'     => Mautic\AssetBundle\ErrorHandler\DropzoneErrorHandler::class,
            ],
            // Override the DropzoneController
            'oneup_uploader.controller.dropzone.class' => [
                'class'     => Mautic\AssetBundle\Controller\UploadController::class,
            ],
        ],
        'fixtures' => [
            'mautic.asset.fixture.asset' => [
                'class'     => Mautic\AssetBundle\DataFixtures\ORM\LoadAssetData::class,
                'tag'       => Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
            ],
        ],
    ],

    'parameters' => [
        'upload_dir'          => '%mautic.application_dir%/media/files',
        'max_size'            => '6',
        'allowed_extensions'  => ['csv', 'doc', 'docx', 'epub', 'gif', 'jpg', 'jpeg', 'mpg', 'mpeg', 'mp3', 'odt', 'odp', 'ods', 'pdf', 'png', 'ppt', 'pptx', 'tif', 'tiff', 'txt', 'xls', 'xlsx', 'wav'],
        'streamed_extensions' => ['gif', 'jpg', 'jpeg', 'mpg', 'mpeg', 'mp3', 'pdf', 'png', 'wav'],
        'allowed_mimetypes'   => [
            'csv'  => 'text/csv',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'epub' => 'application/epub+zip',
            'gif'  => 'image/gif',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'mpg'  => 'video/mpeg',
            'mpeg' => 'video/mpeg',
            'mp3'  => 'audio/mpeg',
            'odt'  => 'application/vnd.oasis.opendocument.text',
            'odp'  => 'application/vnd.oasis.opendocument.presentation',
            'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
            'pdf'  => 'application/pdf',
            'png'  => 'image/png',
            'ppt'  => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'tif'  => 'image/tiff',
            'tiff' => 'image/tiff',
            'txt'  => 'text/plain',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'wav'  => 'audio/wav',
        ],
    ],
];
