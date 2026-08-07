<?php

declare(strict_types=1);

namespace Mautic\EmailBundle;

/**
 * Events available for EmailBundle.
 */
final class EmailEvents
{
    /**
     * The mautic.email_token_replacement event is thrown right before the content is returned.
     *
     * The event listener receives a
     * Mautic\CoreBundle\Event\TokenReplacementEvent instance.
     */
    public const string TOKEN_REPLACEMENT = 'mautic.email_token_replacement';

    /**
     * The mautic.email_address_token_replacement event is thrown right before a email address token needs replacement.
     *
     * The event listener receives a
     * Mautic\CoreBundle\Event\TokenReplacementEvent instance.
     */
    public const string ON_EMAIL_ADDRESS_TOKEN_REPLACEMENT = 'mautic.email_address_token_replacement';

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
     * The mautic.email_pre_send event is dispatched when an email is clicked.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailSendEvent instance.
     */
    public const string EMAIL_PRE_SEND = 'mautic.email_pre_send';

    /**
     * The mautic.email_on_display event is dispatched when an email is viewed via a browser.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailSendEvent instance.
     */
    public const string EMAIL_ON_DISPLAY = 'mautic.email_on_display';

    /**
     * The mautic.email_on_build event is dispatched before displaying the email builder form to allow adding of tokens.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailEvent instance.
     */
    public const string EMAIL_ON_BUILD = 'mautic.email_on_build';

    /**
     * The mautic.email_on_toggle_publish event is dispatched right before an email is toggle publish.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailEvent instance.
     */
    public const string EMAIL_ON_TOGGLE_PUBLISH = 'mautic.email_on_toggle_publish';

    /**
     * The mautic.email_pre_save event is dispatched right before a email is persisted.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailEvent instance.
     */
    public const string EMAIL_PRE_SAVE = 'mautic.email_pre_save';

    /**
     * The mautic.email_post_save event is dispatched right after a email is persisted.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailEvent instance.
     */
    public const string EMAIL_POST_SAVE = 'mautic.email_post_save';

    /**
     * The mautic.email_pre_delete event is dispatched prior to when a email is deleted.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailEvent instance.
     */
    public const string EMAIL_PRE_DELETE = 'mautic.email_pre_delete';

    /**
     * The mautic.email_post_delete event is dispatched after a email is deleted.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailEvent instance.
     */
    public const string EMAIL_POST_DELETE = 'mautic.email_post_delete';

    /**
     * The mautic.monitored_email_config event is dispatched during the configuration in order to inject custom folder locations.
     *
     * The event listener receives a Mautic\CoreBundle\Event\MonitoredEmailEvent instance.
     */
    public const string MONITORED_EMAIL_CONFIG = 'mautic.monitored_email_config';

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
     * The mautic.email_on_reply event is dispatched when an reply came to an email.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailOpenEvent instance.
     */
    public const string EMAIL_ON_REPLY = 'mautic.email_on_reply';

    /**
     * The mautic.email.on_email_validation event is dispatched when an email is validated through the validator.
     *
     * The event listener receives a Mautic\EmailBundle\Event\EmailValidationEvent
     */
    public const string ON_EMAIL_VALIDATION = 'mautic.email.on_email_validation';

    /**
     * The mautic.email.on_sent_email_to_user event is dispatched when email is sent to user.
     *
     * The event listener receives a
     * Mautic\PointBundle\Events\TriggerExecutedEvent
     */
    public const string ON_SENT_EMAIL_TO_USER = 'mautic.email.on_sent_email_to_user';

    /**
     * @deprecated 2.13.0; to be removed in 3.0. Listen to ON_CAMPAIGN_BATCH_ACTION instead.
     *
     * The mautic.email.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     */
    public const string ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.email.on_campaign_trigger_action';

    /**
     * The mautic.email.on_transport_webhook event is fired when an email transport service sends Mautic a webhook request.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\TransportWebhookEvent
     */
    public const string ON_TRANSPORT_WEBHOOK = 'mautic.email.on_transport_webhook';

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

    /**
     * The mautic.email.on_email_stat_pre_save event is fired before an email stat batch is saved.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailStatEvent
     */
    public const string ON_EMAIL_STAT_PRE_SAVE = 'mautic.email.on_email_stat_pre_save';

    /**
     * The mautic.email.on_email_stat_post_save event is fired after an email stat batch is saved.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailStatEvent
     */
    public const string ON_EMAIL_STAT_POST_SAVE = 'mautic.email.on_email_stat_post_save';

    /**
     * The mautic.email.on_edit_submit event is fired after an email edit is successfully submitted.
     *
     * The event listener receives a
     * Mautic\EmailBundle\Event\EmailEditSubmitEvent
     */
    public const string ON_EMAIL_EDIT_SUBMIT = 'mautic.email.on_edit_submit';
}
