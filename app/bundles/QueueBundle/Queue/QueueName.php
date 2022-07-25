<?php

namespace Mautic\QueueBundle\Queue;

/**
 * Class QueueName.
 */
final class QueueName
{
    const EMAIL_HIT         = 'email_hit';
    const PAGE_HIT          = 'page_hit';
    const TRANSPORT_WEBHOOK = 'transport_webhook';
}
