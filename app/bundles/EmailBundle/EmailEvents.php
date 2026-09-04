<?php

declare(strict_types=1);

namespace Mautic\EmailBundle;

final class EmailEvents
{
    /**
     * The mautic.email_on_open event is dispatched when an email is opened.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailOpenEvent instance.
     */
    public const string EMAIL_ON_OPEN = 'mautic.email_on_open';

    /**
     * The mautic.email_on_send event is dispatched when an email is sent.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailSendEvent instance.
     */
    public const string EMAIL_ON_SEND = 'mautic.email_on_send';

    /**
     * The mautic.on_email_parse event is dispatched when a monitored email box retrieves messages.
     *
     * The event listener receives a Mautic\EmailBundle\Event\ParseEmailEvent instance.
     */
    public const string EMAIL_PARSE = 'mautic.on_email_parse';

    /**
     * The mautic.on_email_pre_fetch event is dispatched prior to fetching email through a configured monitored inbox in order to set
     * search criteria for the mail to be fetched.
     *
     * The event listener receives a Mautic\EmailBundle\Event\ParseEmailEvent instance.
     */
    public const string EMAIL_PRE_FETCH = 'mautic.on_email_pre_fetch';

    /**
     * The mautic.on_email_failed event is dispatched when an email has failed to clear the queue and is about to be deleted
     * in order to give a bundle a chance to do an action based on failed email if required.
     *
     * The event listener receives a Mautic\EmailBundle\Event\QueueEmailEvent instance.
     */
    public const string EMAIL_FAILED = 'mautic.on_email_failed';

    /**
     * The mautic.on_email_resend event is dispatched when an attempt to resend an email occurs
     * in order to give a bundle a chance to do an action based on failed email if required.
     *
     * The event listener receives a Mautic\EmailBundle\Event\QueueEmailEvent instance.
     */
    public const string EMAIL_RESEND = 'mautic.on_email_resend';

    /**
     * The mautic.email.on_campaign_batch_action event is dispatched when the campaign action triggers.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent
     */
    public const string ON_CAMPAIGN_BATCH_ACTION = 'mautic.email.on_campaign_batch_action';

    /**
     * The mautic.email.on_campaign_trigger_decision event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     */
    public const string ON_CAMPAIGN_TRIGGER_DECISION = 'mautic.email.on_campaign_trigger_decision';

    /**
     * The mautic.email.on_campaign_trigger_condition event is dispatched when the campaign condition triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     */
    public const string ON_CAMPAIGN_TRIGGER_CONDITION = 'mautic.email.on_campaign_trigger_condition';

    /**
     * The mautic.email.on_sent_email_to_user event is dispatched when email is sent to user.
     *
     * The event listener receives a
     * Mautic\PointBundle\Events\TriggerExecutedEvent
     */
    public const string ON_SENT_EMAIL_TO_USER = 'mautic.email.on_sent_email_to_user';

    /**
     * The mautic.email.on_open_rate_winner event is fired when there is a need to determine open rate winner.
     *
     * The event listener receives a
     * Mautic\CoreBundle\Event\DetermineWinnerEvent
     */
    public const string ON_DETERMINE_OPEN_RATE_WINNER = 'mautic.email.on_open_rate_winner';

    /**
     * The mautic.email.on_open_rate_winner event is fired when there is a need to determine clickthrough rate winner.
     *
     * The event listener receives a
     * Mautic\CoreBundles\Event\DetermineWinnerEvent
     */
    public const string ON_DETERMINE_CLICKTHROUGH_RATE_WINNER = 'mautic.email.on_clickthrough_rate_winner';
}
