<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle;

/**
 * Events available for MauticFocusBundle.
 */
final class FocusEvents
{
    /**
     * The mautic.focus_pre_save event is dispatched right before a focus is persisted.
     *
     * The event listener receives a MauticPlugin\MauticFocusBundle\Event\FocusEvent instance.
     */
    public const string PRE_SAVE = 'mautic.focus_pre_save';

    /**
     * The mautic.focus_post_save event is dispatched right after a focus is persisted.
     *
     * The event listener receives a MauticPlugin\MauticFocusBundle\Event\FocusEvent instance.
     */
    public const string POST_SAVE = 'mautic.focus_post_save';

    /**
     * The mautic.focus_pre_delete event is dispatched before a focus is deleted.
     *
     * The event listener receives a MauticPlugin\MauticFocusBundle\Event\FocusEvent instance.
     */
    public const string PRE_DELETE = 'mautic.focus_pre_delete';

    /**
     * The mautic.focus_post_delete event is dispatched after a focus is deleted.
     *
     * The event listener receives a MauticPlugin\MauticFocusBundle\Event\FocusEvent instance.
     */
    public const string POST_DELETE = 'mautic.focus_post_delete';

    /**
     * The mautic.focus_token_replacent event is dispatched after a load content.
     *
     * The event listener receives a MauticPlugin\MauticFocusBundle\Event\FocusEvent instance.
     */
    public const string TOKEN_REPLACEMENT = 'mautic.focus_token_replacement';

    /**
     * The mautic.focus.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     */
    public const string ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.focus.on_campaign_trigger_action';

    /**
     * The mautic.focus.on_open event is dispatched when an focus is opened.
     *
     * The event listener receives a
     * MauticPlugin\MauticFocusBundle\Event\FocusOpenEvent instance.
     */
    public const string FOCUS_ON_VIEW = 'mautic.focus.on_view';
}
