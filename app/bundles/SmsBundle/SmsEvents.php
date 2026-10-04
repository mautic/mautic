<?php

declare(strict_types=1);

namespace Mautic\SmsBundle;

/**
 * Events available for SmsBundle.
 */
final class SmsEvents
{
    /**
     * The mautic.sms_token_replacement event is thrown right before the content is returned.
     *
     * The event listener receives a
     * Mautic\CoreBundle\Event\TokenReplacementEvent instance.
     */
    public const string TOKEN_REPLACEMENT = 'mautic.sms_token_replacement';

    /**
     * The mautic.sms_on_send event is thrown when a sms is sent.
     *
     * The event listener receives a
     * Mautic\SmsBundle\Event\SmsSendEvent instance.
     */
    public const string SMS_ON_SEND = 'mautic.sms_on_send';

    /**
     * The mautic.sms.on_campaign_trigger_batch_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     */
    public const string ON_CAMPAIGN_TRIGGER_BATCH_ACTION = 'mautic.sms.on_campaign_trigger_batch_action';

    /**
     * The mautic.sms.on_campaign_reply event is dispatched when a SMS reply campaign decision is processed.
     *
     * The event listener receives a Mautic\SmsBundle\Event\ReplyEvent
     */
    public const string ON_CAMPAIGN_REPLY = 'mautic.sms.on_campaign_reply';
}
