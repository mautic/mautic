<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle;

/**
 * Events available for MauticWebhookBundle.
 */
final class WebhookEvents
{
    /**
     * The mautic.webhook_queue_on_add event is thrown as the queue entity is created, before it is persisted to the database.
     *
     * The event listener receives a Mautic\WebhookBundle\Event\WebhookQueueEvent instance.
     */
    public const string WEBHOOK_QUEUE_ON_ADD = 'mautic.webhook_queue_on_add';

    /**
     * The mautic.webhook_pre_execute event is thrown right before a webhook URL is executed.
     *
     * The event listener receives a Mautic\WebhookBundle\Event\WebhookExecuteEvent instance.
     */
    public const string WEBHOOK_PRE_EXECUTE = 'mautic.webhook_pre_execute';

    /**
     * The mautic.webhook_post_execute event is thrown right after a webhook URL is executed.
     *
     * The event listener receives a Mautic\WebhookBundle\Event\WebhookExecuteEvent instance.
     */
    public const string WEBHOOK_POST_EXECUTE = 'mautic.webhook_post_execute';

    /**
     * The mautic.webhook_on_build event is as the webhook form is built.
     *
     * The event listener receives a Mautic\WebhookBundle\Event\WebhookBuild instance.
     */
    public const string WEBHOOK_ON_BUILD = 'mautic.webhook_on_build';

    /**
     * The mautic.webhook.on_campaign_batch_action event is dispatched when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\PendingEvent instance.
     */
    public const string ON_CAMPAIGN_BATCH_ACTION = 'mautic.webhook.on_campaign_batch_action';

    /**
     * The mautic.webhook_on_request event is fired before request is processed.
     *
     * The event listener receives a Mautic\WebhookBundle\Event\WebhookRequestEvent instance.
     */
    public const string WEBHOOK_ON_REQUEST = 'mautic.webhook_on_request';
}
