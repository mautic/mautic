<?php

declare(strict_types=1);

namespace Mautic\AssetBundle;

final class AssetEvents
{
    /**
     * The mautic.asset.on_campaign_trigger_decision event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     */
    public const string ON_CAMPAIGN_TRIGGER_DECISION = 'mautic.asset.on_campaign_trigger_decision';

    /**
     * The mautic.asset.on_download_rate_winner event is fired when there is a need to determine download rate winner.
     *
     * The event listener receives a
     * Mautic\CoreBundles\Event\DetermineWinnerEvent
     */
    public const string ON_DETERMINE_DOWNLOAD_RATE_WINNER = 'mautic.asset.on_download_rate_winner';
}
