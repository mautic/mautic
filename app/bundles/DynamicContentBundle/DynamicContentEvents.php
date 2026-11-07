<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle;

/**
 * Events available for DynamicContentBundle.
 */
final class DynamicContentEvents
{
    /**
     * The mautic.asset.on_campaign_trigger_decision event is fired when the campaign decision triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\DecisionEvent
     */
    public const string ON_CAMPAIGN_TRIGGER_DECISION = 'mautic.dwc.on_campaign_trigger_decision';

    /**
     * The mautic.dwc.on_campaign_batch_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\PendingEvent
     */
    public const string ON_CAMPAIGN_BATCH_ACTION = 'mautic.dwc.on_campaign_batch_action';
}
