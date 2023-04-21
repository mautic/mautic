<?php

namespace Mautic\QueueBundle\Queue;

/**
 * Class QueueConsumerResults.
 */
final class QueueConsumerResults
{
    public const ACKNOWLEDGE        = 'delete';
    public const DO_NOT_ACKNOWLEDGE = 'do_not_acknowledge';
    public const REJECT             = 'do_not_retry';
    public const TEMPORARY_REJECT   = 'temporary_reject';
}
