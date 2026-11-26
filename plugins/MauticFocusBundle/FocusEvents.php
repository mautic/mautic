<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle;

/**
 * Events available for MauticFocusBundle.
 */
final class FocusEvents
{
    /**
     * The mautic.focus.on_campaign_batch_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\PendingEvent
     */
    public const string ON_CAMPAIGN_BATCH_ACTION = 'mautic.focus.on_campaign_batch_action';

    /**
     * The mautic.focus.on_open event is dispatched when an focus is opened.
     *
     * The event listener receives a
     * MauticPlugin\MauticFocusBundle\Event\FocusOpenEvent instance.
     */
    public const string FOCUS_ON_VIEW = 'mautic.focus.on_view';
}
