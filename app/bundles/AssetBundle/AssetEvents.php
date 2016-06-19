<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle;

/**
 * Class AssetEvents
 * Events available for AssetBundle
 *
 * @package Mautic\AssetBundle
 */
final class AssetEvents
{

    /**
     * The mautic.asset_on_download event is thrown when a public asset is being downloaded and a download tracked.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\AssetDownloadEvent instance.
     *
     * @var string
     */
    const ASSET_ON_DOWNLOAD   = 'mautic.asset_on_download';

    /**
     * The mautic.asset_on_remote_browse event is thrown when browsing a remote provider
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\RemoteAssetBrowseEvent instance.
     *
     * @var string
     */
    const ASSET_ON_REMOTE_BROWSE = 'mautic.asset_on_remote_browse';

    /**
     * The mautic.asset_on_upload event is thrown before uploading a file
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\AssetEvent instance.
     *
     * @var string
     */
    const ASSET_ON_UPLOAD   = 'mautic.asset_on_upload';

    /**
     * The mautic.asset_on_display event is thrown before displaying the asset content
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\AssetEvent instance.
     *
     * @var string
     */
    const ASSET_ON_DISPLAY   = 'mautic.asset_on_display';

    /**
     * The mautic.asset_pre_save event is thrown right before a asset is persisted.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\AssetEvent instance.
     *
     * @var string
     */
    const ASSET_PRE_SAVE   = 'mautic.asset_pre_save';

    /**
     * The mautic.asset_post_save event is thrown right after a asset is persisted.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\AssetEvent instance.
     *
     * @var string
     */
    const ASSET_POST_SAVE   = 'mautic.asset_post_save';

    /**
     * The mautic.asset_pre_delete event is thrown prior to when a asset is deleted.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\AssetEvent instance.
     *
     * @var string
     */
    const ASSET_PRE_DELETE   = 'mautic.asset_pre_delete';

    /**
     * The mautic.asset_post_delete event is thrown after a asset is deleted.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\AssetEvent instance.
     *
     * @var string
     */
    const ASSET_POST_DELETE   = 'mautic.asset_post_delete';

    /**
     * The mautic.category_pre_save event is thrown right before a category is persisted.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_PRE_SAVE   = 'mautic.category_pre_save';

    /**
     * The mautic.category_post_save event is thrown right after a category is persisted.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_POST_SAVE   = 'mautic.category_post_save';

    /**
     * The mautic.category_pre_delete event is thrown prior to when a category is deleted.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_PRE_DELETE   = 'mautic.category_pre_delete';


    /**
     * The mautic.category_post_delete event is thrown after a category is deleted.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_POST_DELETE   = 'mautic.category_post_delete';

    /**
     * The mautic.asset.on_campaign_trigger_decision event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_DECISION = 'mautic.asset.on_campaign_trigger_decision';
}
