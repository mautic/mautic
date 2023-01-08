<?php

namespace Mautic\QueueBundle\Queue;

/**
 * Class QueueProtocol.
 */
class QueueProtocol
{
    public const BEANSTALKD = 'beanstalkd';
    public const RABBITMQ   = 'rabbitmq';
}
