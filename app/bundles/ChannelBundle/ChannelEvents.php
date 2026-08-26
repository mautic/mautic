<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle;

final class ChannelEvents
{
    /**
     * The mautic.add_channel event registers communication channels.
     *
     * The event listener receives a Mautic\ChannelBundle\Event\ChannelEvent instance.
     */
    public const string ADD_CHANNEL = 'mautic.add_channel';

    /**
     * The mautic.channel_broadcast event is dispatched by the mautic:send:broadcast command to process communication to pending contacts.
     *
     * The event listener receives a Mautic\ChannelBundle\Event\ChannelBroadcastEvent instance.
     */
    public const string CHANNEL_BROADCAST = 'mautic.channel_broadcast';

    /**
     * The mautic.message_queued event is dispatched to save a message to the queue.
     *
     * The event listener receives a Mautic\ChannelBundle\Event\MessageQueueEvent instance.
     */
    public const string MESSAGE_QUEUED = 'mautic.message_queued';

    /**
     * The mautic.process_message_queue event is dispatched to be processed by a listener.
     *
     * The event listener receives a Mautic\ChannelBundle\Event\MessageQueueProcessEvent instance.
     */
    public const string PROCESS_MESSAGE_QUEUE = 'mautic.process_message_queue';

    /**
     * The mautic.process_message_queue_batch event is dispatched to process a batch of messages by channel and channel ID.
     *
     * The event listener receives a Mautic\ChannelBundle\Event\MessageQueueBatchProcessEvent instance.
     */
    public const string PROCESS_MESSAGE_QUEUE_BATCH = 'mautic.process_message_queue_batch';

    /**
     * The mautic.channel.on_campaign_batch_action event is dispatched when the campaign action triggers.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent
     */
    public const string ON_CAMPAIGN_BATCH_ACTION = 'mautic.channel.on_campaign_batch_action';

    /**
     * The mautic.message_pre_save event is dispatched right before a form is persisted.
     *
     * The event listener receives a
     * Mautic\ChannelEvent\Event\MessageEvent instance.
     */
    public const string MESSAGE_PRE_SAVE = 'mautic.message_pre_save';

    /**
     * The mautic.message_post_save event is dispatched right after a form is persisted.
     *
     * The event listener receives a
     * Mautic\ChannelEvent\Event\MessageEvent instance.
     */
    public const string MESSAGE_POST_SAVE = 'mautic.message_post_save';

    /**
     * The mautic.message_pre_delete event is dispatched before a form is deleted.
     *
     * The event listener receives a
     * Mautic\ChannelEvent\Event\MessageEvent instance.
     */
    public const string MESSAGE_PRE_DELETE = 'mautic.message_pre_delete';

    /**
     * The mautic.message_post_delete event is dispatched after a form is deleted.
     *
     * The event listener receives a
     * Mautic\ChannelEvent\Event\MessageEvent instance.
     */
    public const string MESSAGE_POST_DELETE = 'mautic.message_post_delete';
}
