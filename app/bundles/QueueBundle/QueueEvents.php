<?php

namespace Mautic\QueueBundle;

/**
 * Class MauticQueueEvents
 * Events available for MauticQueueBundle.
 */
final class QueueEvents
{
    public const CONSUME_MESSAGE = 'mautic.queue_consume_message';

    public const PUBLISH_MESSAGE = 'mautic.queue_publish_message';

    public const EMAIL_HIT = 'mautic.queue_email_hit';

    public const PAGE_HIT = 'mautic.queue_page_hit';

    public const TRANSPORT_WEBHOOK = 'mautic.queue_transport_webhook';
}
