<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle;

final class CampaignEvents
{
    /**
     * The mautic.campaign_on_event_decision_evaluation event is dispatched when a campaign decision is to be evaluated.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\DecisionEvent instance.
     */
    public const string ON_EVENT_DECISION_EVALUATION = 'mautic.campaign_on_event_decision_evaluation';

    /**
     * The mautic.campaign_on_event_decision_evaluation event is dispatched when a campaign decision is to be evaluated.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\DecisionEvent instance.
     */
    public const string ON_EVENT_CONDITION_EVALUATION = 'mautic.campaign_on_event_decision_evaluation';

    /**
     * The mautic.campaign_on_event_jump_to_event event is dispatched when a campaign jump to event is triggered.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent instance.
     */
    public const string ON_EVENT_JUMP_TO_EVENT = 'mautic.campaign_on_event_jump_to_event';
}
