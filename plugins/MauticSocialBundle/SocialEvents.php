<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle;

/**
 * Events available for MauticSocialBundle.
 */
final class SocialEvents
{
    /**
     * The mautic.social.on_campaign_batch_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\PendingEvent
     */
    public const string ON_CAMPAIGN_BATCH_ACTION = 'mautic.social.on_campaign_batch_action';
}
