<?php

namespace Mautic\NotificationBundle;

/**
 * Events available for NotificationBundle.
 */
final class NotificationEvents
{
    /**
     * The mautic.notification_token_replacement event is thrown right before the content is returned.
     *
     * The event listener receives a
     * Mautic\CoreBundle\Event\TokenReplacementEvent instance.
     *
     * @var string
     */
    public const TOKEN_REPLACEMENT = 'mautic.notification_token_replacement';

    /**
     * The mautic.notification_form_action_send event is thrown when a notification is sent
     * as part of a form action.
     *
     * The event listener receives a
     * Mautic\NotificationBundle\Event\SendingNotificationEvent instance.
     *
     * @var string
     */
    public const NOTIFICATION_ON_FORM_ACTION_SEND = 'mautic.notification_form_action_send';

    /**
     * The mautic.notification_on_send event is thrown when a notification is sent.
     *
     * The event listener receives a
     * Mautic\NotificationBundle\Event\NotificationSendEvent instance.
     *
     * @var string
     */
    public const NOTIFICATION_ON_SEND = 'mautic.notification_on_send';

    /**
     * The mautic.notification_pre_save event is thrown right before a notification is persisted.
     *
     * The event listener receives a
     * Mautic\NotificationBundle\Event\NotificationEvent instance.
     *
     * @var string
     */
    public const NOTIFICATION_PRE_SAVE = 'mautic.notification_pre_save';

    /**
     * The mautic.notification_post_save event is thrown right after a notification is persisted.
     *
     * The event listener receives a
     * Mautic\NotificationBundle\Event\NotificationEvent instance.
     *
     * @var string
     */
    public const NOTIFICATION_POST_SAVE = 'mautic.notification_post_save';

    /**
     * The mautic.notification_pre_delete event is thrown prior to when a notification is deleted.
     *
     * The event listener receives a
     * Mautic\NotificationBundle\Event\NotificationEvent instance.
     *
     * @var string
     */
    public const NOTIFICATION_PRE_DELETE = 'mautic.notification_pre_delete';

    /**
     * The mautic.notification_post_delete event is thrown after a notification is deleted.
     *
     * The event listener receives a
     * Mautic\NotificationBundle\Event\NotificationEvent instance.
     *
     * @var string
     */
    public const NOTIFICATION_POST_DELETE = 'mautic.notification_post_delete';

    /**
     * The mautic.notification.on_batch_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\PendingEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_BATCH_ACTION = 'mautic.notification.on_batch_trigger_action';

    /**
     * The mautic.notification.on_campaign_trigger_condition event is fired when the campaign condition triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    public const ON_CAMPAIGN_TRIGGER_CONDITION = 'mautic.notification.on_campaign_trigger_notification';
}
