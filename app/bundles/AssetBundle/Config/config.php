<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'routes' => array(
        'main' => array(
            'mautic_asset_buildertoken_index' => array(
                'path' => '/asset/buildertokens/{page}',
                'controller' => 'MauticAssetBundle:SubscribedEvents\BuilderToken:index'
            ),
            'mautic_asset_index' => array(
                'path' => '/assets/{page}',
                'controller' => 'MauticAssetBundle:Asset:index'
            ),
            'mautic_asset_remote' => array(
                'path' => '/assets/remote',
                'controller' => 'MauticAssetBundle:Asset:remote',
            ),
            'mautic_asset_action' => array(
                'path' => '/assets/{objectAction}/{objectId}',
                'controller' => 'MauticAssetBundle:Asset:execute',
            )
        ),
        'api' => array(
            'mautic_api_getassets' => array(
                'path' => '/assets',
                'controller' => 'MauticAssetBundle:Api\AssetApi:getEntities'
            ),
            'mautic_api_getasset' => array(
                'path' => '/assets/{id}',
                'controller' => 'MauticAssetBundle:Api\AssetApi:getEntity'
            )
        ),
        'public' => array(
            'mautic_asset_download' => array(
                'path' => '/asset/{slug}',
                'controller' => 'MauticAssetBundle:Public:download',
                'defaults' => array(
                    "slug"    => ''
                )
            )
        )
    ),

    'menu' => array(
        'main' => array(
            'items'    => array(
                'mautic.asset.assets' => array(
                    'route'     => 'mautic_asset_index',
                    'access'    => array('asset:assets:viewown', 'asset:assets:viewother'),
                    'parent'    => 'mautic.core.components',
                    'priority'  => 300,
                )
            )
        )
    ),

    'categories' => array(
        'asset' => null
    ),

    'services' => array(
        'events' => array(
            'mautic.asset.subscriber' => array(
                'class' => 'Mautic\AssetBundle\EventListener\AssetSubscriber'
            ),
            'mautic.asset.pointbundle.subscriber' => array(
                'class' => 'Mautic\AssetBundle\EventListener\PointSubscriber'
            ),
            'mautic.asset.formbundle.subscriber' => array(
                'class' => 'Mautic\AssetBundle\EventListener\FormSubscriber'
            ),
            'mautic.asset.campaignbundle.subscriber' => array(
                'class' => 'Mautic\AssetBundle\EventListener\CampaignSubscriber'
            ),
            'mautic.asset.reportbundle.subscriber' => array(
                'class' => 'Mautic\AssetBundle\EventListener\ReportSubscriber'
            ),
            'mautic.asset.builder.subscriber' => array(
                'class' => 'Mautic\AssetBundle\EventListener\BuilderSubscriber'
            ),
            'mautic.asset.leadbundle.subscriber' => array(
                'class' => 'Mautic\AssetBundle\EventListener\LeadSubscriber'
            ),
            'mautic.asset.pagebundle.subscriber' => array(
                'class' => 'Mautic\AssetBundle\EventListener\PageSubscriber'
            ),
            'mautic.asset.emailbundle.subscriber' => array(
                'class' => 'Mautic\AssetBundle\EventListener\EmailSubscriber'
            ),
            'mautic.asset.configbundle.subscriber' => array(
                'class' => 'Mautic\AssetBundle\EventListener\ConfigSubscriber'
            ),
            'mautic.asset.search.subscriber' => array(
                'class' => 'Mautic\AssetBundle\EventListener\SearchSubscriber'
            ),
            'oneup_uploader.pre_upload' => array(
                'class' => 'Mautic\AssetBundle\EventListener\UploadSubscriber'
            ),
            'mautic.asset.dashboard.subscriber' => array(
                'class' => 'Mautic\AssetBundle\EventListener\DashboardSubscriber'
            ),
        ),
        'forms' => array(
            'mautic.form.type.asset' => array(
                'class' => 'Mautic\AssetBundle\Form\Type\AssetType',
                'arguments' => 'mautic.factory',
                'alias' => 'asset'
            ),
            'mautic.form.type.pointaction_assetdownload' => array(
                'class' => 'Mautic\AssetBundle\Form\Type\PointActionAssetDownloadType',
                'alias' => 'pointaction_assetdownload'
            ),
            'mautic.form.type.campaignevent_assetdownload' => array(
                'class' => 'Mautic\AssetBundle\Form\Type\CampaignEventAssetDownloadType',
                'alias' => 'campaignevent_assetdownload'
            ),
            'mautic.form.type.formsubmit_assetdownload' => array(
                'class' => 'Mautic\AssetBundle\Form\Type\FormSubmitActionDownloadFileType',
                'alias' => 'asset_submitaction_downloadfile'
            ),
            'mautic.form.type.assetlist' => array(
                'class' => 'Mautic\AssetBundle\Form\Type\AssetListType',
                'arguments' => 'mautic.factory',
                'alias' => 'asset_list'
            ),
            'mautic.form.type.assetconfig' => array(
                'class' => 'Mautic\AssetBundle\Form\Type\ConfigType',
                'arguments' => 'mautic.factory',
                'alias' => 'assetconfig'
            ),
            'mautic.form.type.asset_dashboard_downloads_in_time_widget' => array(
                'class'     => 'Mautic\AssetBundle\Form\Type\DashboardDownloadsInTimeWidgetType',
                'alias'     => 'asset_dashboard_downloads_in_time_widget'
            )
        ),
        'others' => array(
            'mautic.asset.upload.error.handler' => array(
                'class' => 'Mautic\AssetBundle\ErrorHandler\DropzoneErrorHandler',
                'arguments' => 'mautic.factory'
            ),
            // Override the DropzoneController
            'oneup_uploader.controller.dropzone.class' => 'Mautic\AssetBundle\Controller\UploadController'
        )
    ),

    'parameters' => array(
        'upload_dir'            => '%kernel.root_dir%/../media/files',
        'max_size'              => '6',
        'allowed_extensions'    => array('csv', 'doc', 'docx', 'epub', 'gif', 'jpg', 'jpeg', 'mpg', 'mpeg', 'mp3', 'odt', 'odp', 'ods', 'pdf', 'png', 'ppt', 'pptx', 'tif', 'tiff', 'txt', 'xls', 'xlsx', 'wav')
    )
);
