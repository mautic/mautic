<?php

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
     *
     * @var string
     */
    public const TOKEN_REPLACEMENT = 'mautic.sms_token_replacement';

    /**
     * The mautic.sms_on_send event is thrown when a sms is sent.
     *
     * The event listener receives a
     * Mautic\SmsBundle\Event\SmsSendEvent instance.
     *
     * @var string
     */
    public const SMS_ON_SEND = 'mautic.sms_on_send';

    /**
     * The mautic.sms_pre_save event is thrown right before a sms is persisted.
     *
     * The event listener receives a
     * Mautic\SmsBundle\Event\SmsEvent instance.
     *
     * @var string
     */
    public const SMS_PRE_SAVE = 'mautic.sms_pre_save';

    /**
     * The mautic.sms_post_save event is thrown right after a sms is persisted.
     *
     * The event listener receives a
     * Mautic\SmsBundle\Event\SmsEvent instance.
     *
     * @var string
     */
    public const SMS_POST_SAVE = 'mautic.sms_post_save';

    /**
     * The mautic.sms_pre_delete event is thrown prior to when a sms is deleted.
     *
     * The event listener receives a
     * Mautic\SmsBundle\Event\SmsEvent instance.
     *
     * @var string
     */
    public const SMS_PRE_DELETE = 'mautic.sms_pre_delete';

    /**
     * The mautic.sms_post_delete event is thrown after a sms is deleted.
     *
     * The event listener receives a
     * Mautic\SmsBundle\Event\SmsEvent instance.
     *
     * @var string
     */
    public const SMS_POST_DELETE = 'mautic.sms_post_delete';

    /**
     * The mautic.sms.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.sms.on_campaign_trigger_action';

    /**
     * The mautic.sms.on_reply event is dispatched when a SMS service receives a reply.
     *
     * The event listener receives a Mautic\SmsBundle\Event\ReplyEvent
     *
     * @var string
     */
    public const ON_REPLY = 'mautic.sms.on_reply';

    /**
     * The mautic.sms.on_campaign_reply event is dispatched when a SMS reply campaign decision is processed.
     *
     * The event listener receives a Mautic\SmsBundle\Event\ReplyEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_REPLY = 'mautic.sms.on_campaign_reply';
}
