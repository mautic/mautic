<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle;

/**
 * Events available for DynamicContentBundle.
 */
final class DynamicContentEvents
{
    /**
     * The mautic.dwc_token_replacement event is thrown right before the content is returned.
     *
     * The event listener receives a
     * Mautic\CoreBundle\Event\TokenReplacementEvent instance.
     */
    public const string TOKEN_REPLACEMENT = 'mautic.dwc_token_replacement';

    /**
     * The mautic.dwc_pre_save event is thrown right before a asset is persisted.
     *
     * The event listener receives a
     * Mautic\DynamicContentBundle\Event\DynamicContentEvent instance.
     */
    public const string PRE_SAVE = 'mautic.dwc_pre_save';

    /**
     * The mautic.dwc_post_save event is thrown right after a asset is persisted.
     *
     * The event listener receives a
     * Mautic\DynamicContentBundle\Event\DynamicContentEvent instance.
     */
    public const string POST_SAVE = 'mautic.dwc_post_save';

    /**
     * The mautic.dwc_pre_delete event is thrown prior to when a asset is deleted.
     *
     * The event listener receives a
     * Mautic\DynamicContentBundle\Event\DynamicContentEvent instance.
     */
    public const string PRE_DELETE = 'mautic.dwc_pre_delete';

    /**
     * The mautic.dwc_post_delete event is thrown after a asset is deleted.
     *
     * The event listener receives a
     * Mautic\DynamicContentBundle\Event\DynamicContentEvent instance.
     */
    public const string POST_DELETE = 'mautic.dwc_post_delete';

    /**
     * The mautic.category_pre_save event is thrown right before a category is persisted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     */
    public const string CATEGORY_PRE_SAVE = 'mautic.category_pre_save';

    /**
     * The mautic.category_post_save event is thrown right after a category is persisted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     */
    public const string CATEGORY_POST_SAVE = 'mautic.category_post_save';

    /**
     * The mautic.category_pre_delete event is thrown prior to when a category is deleted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     */
    public const string CATEGORY_PRE_DELETE = 'mautic.category_pre_delete';

    /**
     * The mautic.category_post_delete event is thrown after a category is deleted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     */
    public const string CATEGORY_POST_DELETE = 'mautic.category_post_delete';

    /**
     * The mautic.asset.on_campaign_trigger_decision event is fired when the campaign decision triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     */
    public const string ON_CAMPAIGN_TRIGGER_DECISION = 'mautic.dwc.on_campaign_trigger_decision';

    /**
     * The mautic.dwc.on_campaign_batch_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\PendingEvent
     */
    public const string ON_CAMPAIGN_BATCH_ACTION = 'mautic.dwc.on_campaign_batch_action';

    /**
     * The mautic.dwc.on_contact_filters_evaluate event is fired when dynamic content's decision's
     * filters need to be evaluated.
     *
     * The event listener receives a
     * Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent
     */
    public const string ON_CONTACTS_FILTER_EVALUATE = 'mautic.dwc.on_contact_filters_evaluate';
}
