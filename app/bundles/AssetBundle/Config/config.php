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
                'controller'      => \Mautic\AssetBundle\Controller\Api\AssetApiController::class,
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
        'asset' => null,
    ],

    'services' => [
        'permissions' => [
            'mautic.asset.permissions' => [
                'class'     => \Mautic\AssetBundle\Security\Permissions\AssetPermissions::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
        'others' => [
            'mautic.asset.upload.error.handler' => [
                'class'     => \Mautic\AssetBundle\ErrorHandler\DropzoneErrorHandler::class,
                'arguments' => 'mautic.factory',
            ],
            // Override the DropzoneController
            'oneup_uploader.controller.dropzone.class' => \Mautic\AssetBundle\Controller\UploadController::class,
        ],
        'fixtures' => [
            'mautic.asset.fixture.asset' => [
                'class'     => \Mautic\AssetBundle\DataFixtures\ORM\LoadAssetData::class,
                'tag'       => \Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG,
            ],
        ],
        'repositories' => [
            'mautic.asset.repository.download' => [
                'class'     => Doctrine\ORM\EntityRepository::class,
                'factory'   => ['@doctrine.orm.entity_manager', 'getRepository'],
                'arguments' => \Mautic\AssetBundle\Entity\Download::class,
            ],
        ],
    ],

    'parameters' => [
        'upload_dir'          => '%mautic.application_dir%/media/files',
        'max_size'            => '6',
        'allowed_extensions'  => ['csv', 'doc', 'docx', 'epub', 'gif', 'jpg', 'jpeg', 'mpg', 'mpeg', 'mp3', 'odt', 'odp', 'ods', 'pdf', 'png', 'ppt', 'pptx', 'tif', 'tiff', 'txt', 'xls', 'xlsx', 'wav'],
        'streamed_extensions' => ['gif', 'jpg', 'jpeg', 'mpg', 'mpeg', 'mp3', 'pdf', 'png', 'wav'],
    ],
];
