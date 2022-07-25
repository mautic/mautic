<?php

namespace Mautic\AssetBundle;

/**
 * Events available for AssetBundle.
 */
final class AssetEvents
{
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

    /**
     * The mautic.asset.on_download_rate_winner event is fired when there is a need to determine download rate winner.
     *
     * The event listener receives a
     * Mautic\CoreBundles\Event\DetermineWinnerEvent
     *
     * @var string
     */
    const ON_DETERMINE_DOWNLOAD_RATE_WINNER = 'mautic.asset.on_download_rate_winner';
}
