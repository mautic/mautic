<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle;

/**
 * Class DynamicContentEvents
 * Events available for DynamicContentBundle.
 */
final class DynamicContentEvents
{
    /**
     * The mautic.dwc_token_replacement event is thrown right before the content is returned.
     *
     * The event listener receives a
     * Mautic\CoreBundle\Event\TokenReplacementEvent instance.
     *
     * @var string
     */
    const TOKEN_REPLACEMENT = 'mautic.dwc_token_replacement';

    /**
     * The mautic.dwc_pre_save event is thrown right before a asset is persisted.
     *
     * The event listener receives a
     * Mautic\DynamicContentBundle\Event\DynamicContentEvent instance.
     *
     * @var string
     */
    const PRE_SAVE = 'mautic.dwc_pre_save';

    /**
     * The mautic.dwc_post_save event is thrown right after a asset is persisted.
     *
     * The event listener receives a
     * Mautic\DynamicContentBundle\Event\DynamicContentEvent instance.
     *
     * @var string
     */
    const POST_SAVE = 'mautic.dwc_post_save';

    /**
     * The mautic.dwc_pre_delete event is thrown prior to when a asset is deleted.
     *
     * The event listener receives a
     * Mautic\DynamicContentBundle\Event\DynamicContentEvent instance.
     *
     * @var string
     */
    const PRE_DELETE = 'mautic.dwc_pre_delete';

    /**
     * The mautic.dwc_post_delete event is thrown after a asset is deleted.
     *
     * The event listener receives a
     * Mautic\DynamicContentBundle\Event\DynamicContentEvent instance.
     *
     * @var string
     */
    const POST_DELETE = 'mautic.dwc_post_delete';

    /**
     * The mautic.category_pre_save event is thrown right before a category is persisted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_PRE_SAVE = 'mautic.category_pre_save';

    /**
     * The mautic.category_post_save event is thrown right after a category is persisted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_POST_SAVE = 'mautic.category_post_save';

    /**
     * The mautic.category_pre_delete event is thrown prior to when a category is deleted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_PRE_DELETE = 'mautic.category_pre_delete';

    /**
     * The mautic.category_post_delete event is thrown after a category is deleted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_POST_DELETE = 'mautic.category_post_delete';

    /**
     * The mautic.asset.on_campaign_trigger_decision event is fired when the campaign decision triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_DECISION = 'mautic.dwc.on_campaign_trigger_decision';

    /**
     * The mautic.asset.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.dwc.on_campaign_trigger_action';
}
