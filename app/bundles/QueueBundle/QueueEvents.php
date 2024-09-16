<?php

namespace Mautic\QueueBundle;

/**
 * Class MauticQueueEvents
 * Events available for MauticQueueBundle.
 */
final class QueueEvents
{
    const CONSUME_MESSAGE = 'mautic.queue_consume_message';

    const PUBLISH_MESSAGE = 'mautic.queue_publish_message';

    const EMAIL_HIT = 'mautic.queue_email_hit';

    const PAGE_HIT = 'mautic.queue_page_hit';

    const TRANSPORT_WEBHOOK = 'mautic.queue_transport_webhook';
}
