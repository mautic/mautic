<?php

namespace Mautic\QueueBundle\Queue;

/**
 * Class QueueConsumerResults.
 */
final class QueueConsumerResults
{
    const ACKNOWLEDGE        = 'delete';
    const DO_NOT_ACKNOWLEDGE = 'do_not_acknowledge';
    const REJECT             = 'do_not_retry';
    const TEMPORARY_REJECT   = 'temporary_reject';
}
