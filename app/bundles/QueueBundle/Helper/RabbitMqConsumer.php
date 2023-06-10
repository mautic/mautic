<?php

namespace Mautic\QueueBundle\Helper;

use Mautic\QueueBundle\Queue\QueueConsumerResults;
use Mautic\QueueBundle\Queue\QueueService;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqConsumer implements ConsumerInterface
{
    /**
     * RabbitMqConsumer constructor.
     */
    public function __construct(private QueueService $queueService)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function execute(AMQPMessage $msg)
    {
        $event = $this->queueService->dispatchConsumerEventFromPayload($msg->body);

        if (QueueConsumerResults::TEMPORARY_REJECT === $event->getResult()) {
            return static::MSG_REJECT_REQUEUE;
        } elseif (QueueConsumerResults::ACKNOWLEDGE === $event->getResult()) {
            return static::MSG_ACK;
        } elseif (QueueConsumerResults::REJECT === $event->getResult()) {
            return static::MSG_REJECT;
        } else {
            return static::MSG_SINGLE_NACK_REQUEUE;
        }
    }
}
