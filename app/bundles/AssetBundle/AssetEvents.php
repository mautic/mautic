<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle;

/**
 * Class AssetEvents
 * Events available for AssetBundle.
 */
final class AssetEvents
{
    /**
     * The mautic.asset_on_download event is dispatched when a public asset is being downloaded and a download tracked.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\AssetEvent instance.
     *
     * @var string
     *
     * @deprecated 2.0 - to be removed in 3.0
     */
    const ASSET_ON_DOWNLOAD = 'mautic.asset_on_download';

    /**
     * The mautic.asset_on_load event is dispatched when a public asset is downloaded, publicly viewed, or redirected to (remote).
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\AssetLoadEvent instance.
     *
     * @var string
     */
    const ASSET_ON_LOAD = 'mautic.asset_on_load';

    /**
     * The mautic.asset_on_remote_browse event is dispatched when browsing a remote provider.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\RemoteAssetBrowseEvent instance.
     *
     * @var string
     */
    const ASSET_ON_REMOTE_BROWSE = 'mautic.asset_on_remote_browse';

    /**
     * The mautic.asset_on_upload event is dispatched before uploading a file.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\AssetEvent instance.
     *
     * @var string
     */
    const ASSET_ON_UPLOAD = 'mautic.asset_on_upload';

    /**
     * The mautic.asset_pre_save event is dispatched right before a asset is persisted.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\AssetEvent instance.
     *
     * @var string
     */
    const ASSET_PRE_SAVE = 'mautic.asset_pre_save';

    /**
     * The mautic.asset_post_save event is dispatched right after a asset is persisted.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\AssetEvent instance.
     *
     * @var string
     */
    const ASSET_POST_SAVE = 'mautic.asset_post_save';

    /**
     * The mautic.asset_pre_delete event is dispatched prior to when a asset is deleted.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\AssetEvent instance.
     *
     * @var string
     */
    const ASSET_PRE_DELETE = 'mautic.asset_pre_delete';

    /**
     * The mautic.asset_post_delete event is dispatched after a asset is deleted.
     *
     * The event listener receives a
     * Mautic\AssetBundle\Event\AssetEvent instance.
     *
     * @var string
     */
    const ASSET_POST_DELETE = 'mautic.asset_post_delete';

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
