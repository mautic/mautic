<?php

namespace Mautic\QueueBundle\Queue;

/**
 * Class QueueName.
 */
final class QueueName
{
    public const EMAIL_HIT         = 'email_hit';
    public const PAGE_HIT          = 'page_hit';
    public const TRANSPORT_WEBHOOK = 'transport_webhook';
}
