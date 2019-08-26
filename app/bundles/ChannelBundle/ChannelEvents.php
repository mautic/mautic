<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle;

/**
 * Class ChannelEvents.
 */
final class ChannelEvents
{
    /**
     * The mautic.add_channel event registers communication channels.
     *
     * The event listener receives a Mautic\ChannelBundle\Event\ChannelEvent instance.
     *
     * @var string
     */
    const ADD_CHANNEL = 'mautic.add_channel';

    /**
     * The mautic.channel_broadcast event is dispatched by the mautic:send:broadcast command to process communication to pending contacts.
     *
     * The event listener receives a Mautic\ChannelBundle\Event\ChannelBroadcastEvent instance.
     *
     * @var string
     */
    const CHANNEL_BROADCAST = 'mautic.channel_broadcast';

    /**
     * The mautic.message_queued event is dispatched to save a message to the queue.
     *
     * The event listener receives a Mautic\ChannelBundle\Event\MessageQueueEvent instance.
     *
     * @var string
     */
    const MESSAGE_QUEUED = 'mautic.message_queued';

    /**
     * The mautic.process_message_queue event is dispatched to be processed by a listener.
     *
     * The event listener receives a Mautic\ChannelBundle\Event\MessageQueueProcessEvent instance.
     *
     * @var string
     */
    const PROCESS_MESSAGE_QUEUE = 'mautic.process_message_queue';

    /**
     * The mautic.process_message_queue_batch event is dispatched to process a batch of messages by channel and channel ID.
     *
     * The event listener receives a Mautic\ChannelBundle\Event\MessageQueueBatchProcessEvent instance.
     *
     * @var string
     */
    const PROCESS_MESSAGE_QUEUE_BATCH = 'mautic.process_message_queue_batch';

    /**
     * The mautic.channel.on_campaign_batch_action event is dispatched when the campaign action triggers.
     *
     * The event listener receives a Mautic\CampaignBundle\Event\PendingEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_BATCH_ACTION = 'mautic.channel.on_campaign_batch_action';

    /**
     * The mautic.message_pre_save event is dispatched right before a form is persisted.
     *
     * The event listener receives a
     * Mautic\ChannelEvent\Event\MessageEvent instance.
     *
     * @var string
     */
    const MESSAGE_PRE_SAVE = 'mautic.message_pre_save';

    /**
     * The mautic.message_post_save event is dispatched right after a form is persisted.
     *
     * The event listener receives a
     * Mautic\ChannelEvent\Event\MessageEvent instance.
     *
     * @var string
     */
    const MESSAGE_POST_SAVE = 'mautic.message_post_save';

    /**
     * The mautic.message_pre_delete event is dispatched before a form is deleted.
     *
     * The event listener receives a
     * Mautic\ChannelEvent\Event\MessageEvent instance.
     *
     * @var string
     */
    const MESSAGE_PRE_DELETE = 'mautic.message_pre_delete';

    /**
     * The mautic.message_post_delete event is dispatched after a form is deleted.
     *
     * The event listener receives a
     * Mautic\ChannelEvent\Event\MessageEvent instance.
     *
     * @var string
     */
    const MESSAGE_POST_DELETE = 'mautic.message_post_delete';

    /**
     * @deprecated 2.13.0 to be removed in 3.0; Listen to ON_CAMPAIGN_BATCH_ACTION instead.
     * The mautic.channel.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.channel.on_campaign_trigger_action';
}
