<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle;

/**
 * Class CampaignEvents
 * Events available for CampaignBundle.
 */
final class CampaignEvents
{
    /**
     * The mautic.campaign_pre_save event is dispatched right before a form is persisted.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignEvent instance.
     *
     * @var string
     */
    const CAMPAIGN_PRE_SAVE = 'mautic.campaign_pre_save';

    /**
     * The mautic.campaign_post_save event is dispatched right after a form is persisted.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignEvent instance.
     *
     * @var string
     */
    const CAMPAIGN_POST_SAVE = 'mautic.campaign_post_save';

    /**
     * The mautic.campaign_pre_delete event is dispatched before a form is deleted.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignEvent instance.
     *
     * @var string
     */
    const CAMPAIGN_PRE_DELETE = 'mautic.campaign_pre_delete';

    /**
     * The mautic.campaign_post_delete event is dispatched after a form is deleted.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignEvent instance.
     *
     * @var string
     */
    const CAMPAIGN_POST_DELETE = 'mautic.campaign_post_delete';

    /**
     * The mautic.campaign_on_build event is dispatched before displaying the campaign builder form to allow adding of custom actions.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignBuilderEvent instance.
     *
     * @var string
     */
    const CAMPAIGN_ON_BUILD = 'mautic.campaign_on_build';

    /**
     * The mautic.campaign_on_trigger event is dispatched from the mautic:campaign:trigger command.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignTriggerEvent instance.
     *
     * @var string
     */
    const CAMPAIGN_ON_TRIGGER = 'mautic.campaign_on_trigger';

    /**
     * The mautic.campaign_on_leadchange event is dispatched when a lead was added or removed from the campaign.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignLeadChangeEvent instance.
     *
     * @var string
     */
    const CAMPAIGN_ON_LEADCHANGE = 'mautic.campaign_on_leadchange';

    /**
     * The mautic.campaign_on_leadchange event is dispatched if a batch of leads are changed from CampaignModel::rebuildCampaignLeads().
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignLeadChangeEvent instance.
     *
     * @var string
     */
    const LEAD_CAMPAIGN_BATCH_CHANGE = 'mautic.lead_campaign_batch_change';

    /**
     * The mautic.campaign_on_event_executed event is dispatched when a campaign event is executed.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\ExecutedEvent instance.
     *
     * @var string
     */
    const ON_EVENT_EXECUTED = 'mautic.campaign_on_event_executed';

    /**
     * The mautic.campaign_on_event_executed_batch event is dispatched when a batch of campaign events are executed.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\ExecutedBatchEvent instance.
     *
     * @var string
     */
    const ON_EVENT_EXECUTED_BATCH = 'mautic.campaign_on_event_executed_batch';

    /**
     * The mautic.campaign_on_event_scheduled event is dispatched when a campaign event is scheduled or scheduling is modified.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\ScheduledEvent instance.
     *
     * @var string
     */
    const ON_EVENT_SCHEDULED = 'mautic.campaign_on_event_scheduled';

    /**
     * The mautic.campaign_on_event_scheduled_batch event is dispatched when a batch of events are scheduled at once.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\ScheduledBatchEvent instance.
     *
     * @var string
     */
    const ON_EVENT_SCHEDULED_BATCH = 'mautic.campaign_on_event_scheduled_batch';

    /**
     * The mautic.campaign_on_event_failed event is dispatched when an event fails for whatever reason.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\FailedEvent instance.
     *
     * @var string
     */
    const ON_EVENT_FAILED = 'mautic.campaign_on_event_failed';

    /**
     * The mautic.campaign_on_event_decision_evaluation event is dispatched when a campaign decision is to be evaluated.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\DecisionEvent instance.
     *
     * @var string
     */
    const ON_EVENT_DECISION_EVALUATION = 'mautic.campaign_on_event_decision_evaluation';

    /**
     * The mautic.campaign_on_event_decision_evaluation_results event is dispatched when a batch of contacts were evaluted for a decision.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\DecisionBatchEvent instance.
     *
     * @var string
     */
    const ON_EVENT_DECISION_EVALUATION_RESULTS = 'mautic.campaign_on_event_decision_evaluation_results';

    /**
     * The mautic.campaign_on_event_decision_evaluation event is dispatched when a campaign decision is to be evaluated.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\DecisionEvent instance.
     *
     * @var string
     */
    const ON_EVENT_CONDITION_EVALUATION = 'mautic.campaign_on_event_decision_evaluation';

    /**
     * The mautic.campaign_on_event_jump_to_event event is dispatched when a campaign jump to event is triggered.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent instance.
     *
     * @var string
     */
    const ON_EVENT_JUMP_TO_EVENT = 'mautic.campaign_on_event_jump_to_event';

    /**
     * The mautic.lead.on_campaign_action_change_membership event is dispatched when the campaign action to change a contact's membership is executed.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_ACTION_CHANGE_MEMBERSHIP = 'mautic.lead.on_campaign_action_change_membership';

    /**
     * @deprecated 2.13.0; to be removed in 3.0. Listen to ON_EVENT_EXECUTED and ON_EVENT_FAILED
     *
     * The mautic.campaign_on_event_execution event is dispatched when a campaign event is executed.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\CampaignExecutionEvent instance.
     *
     * @var string
     */
    const ON_EVENT_EXECUTION = 'mautic.campaign_on_event_execution';

    /**
     * @deprecated 2.13.0; to be removed in 3.0; Listen to ON_EVENT_DECISION_EVALUATION instead
     *
     * The mautic.campaign_on_event_decision_trigger event is dispatched after a lead decision triggers a set of actions or if the decision is set
     * as a root level event.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\CampaignDecisionEvent instance.
     *
     * @var string
     */
    const ON_EVENT_DECISION_TRIGGER = 'mautic.campaign_on_event_decision_trigger';
}
