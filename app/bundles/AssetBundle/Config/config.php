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
                'controller'      => 'Mautic\AssetBundle\Controller\Api\AssetApiController',
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
        'forms' => [
            'mautic.form.type.asset' => [
                'class'     => \Mautic\AssetBundle\Form\Type\AssetType::class,
                'arguments' => [
                    'translator',
                    'mautic.asset.model.asset',
                ],
            ],
            'mautic.form.type.pointaction_assetdownload' => [
                'class' => \Mautic\AssetBundle\Form\Type\PointActionAssetDownloadType::class,
            ],
            'mautic.form.type.campaignevent_assetdownload' => [
                'class' => \Mautic\AssetBundle\Form\Type\CampaignEventAssetDownloadType::class,
            ],
            'mautic.form.type.formsubmit_assetdownload' => [
                'class' => \Mautic\AssetBundle\Form\Type\FormSubmitActionDownloadFileType::class,
            ],
            'mautic.form.type.assetlist' => [
                'class'     => \Mautic\AssetBundle\Form\Type\AssetListType::class,
                'arguments' => [
                    'mautic.security',
                    'mautic.asset.model.asset',
                    'mautic.helper.user',
                ],
            ],
            'mautic.form.type.assetconfig' => [
                'class' => \Mautic\AssetBundle\Form\Type\ConfigType::class,
            ],
        ],
        'others' => [
            'mautic.asset.upload.error.handler' => [
                'class'     => \Mautic\AssetBundle\ErrorHandler\DropzoneErrorHandler::class,
                'arguments' => 'mautic.factory',
            ],
            // Override the DropzoneController
            'oneup_uploader.controller.dropzone.class' => \Mautic\AssetBundle\Controller\UploadController::class,
            'mautic.asset.helper.token'                => [
                'class'     => \Mautic\AssetBundle\Helper\TokenHelper::class,
                'arguments' => 'mautic.asset.model.asset',
            ],
        ],
        'models' => [
            'mautic.asset.model.asset' => [
                'class'     => \Mautic\AssetBundle\Model\AssetModel::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.category.model.category',
                    'request_stack',
                    'mautic.helper.ip_lookup',
                    'mautic.helper.core_parameters',
                    'mautic.lead.service.device_creator_service',
                    'mautic.lead.factory.device_detector_factory',
                    'mautic.lead.service.device_tracking_service',
                    'mautic.tracker.contact',
                ],
            ],
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
        'upload_dir'         => '%kernel.project_dir%/media/files',
        'max_size'           => '6',
        'allowed_extensions' => ['csv', 'doc', 'docx', 'epub', 'gif', 'jpg', 'jpeg', 'mpg', 'mpeg', 'mp3', 'odt', 'odp', 'ods', 'pdf', 'png', 'ppt', 'pptx', 'tif', 'tiff', 'txt', 'xls', 'xlsx', 'wav'],
    ],
];
