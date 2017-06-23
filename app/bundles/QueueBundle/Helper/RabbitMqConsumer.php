<?php

namespace Mautic\QueueBundle\Helper;

use Mautic\QueueBundle\Queue\QueueConsumerResults;
use Mautic\QueueBundle\Queue\QueueService;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqConsumer implements ConsumerInterface
{
    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * RabbitMqConsumer constructor.
     *
     * @param QueueService $queueService
     */
    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(AMQPMessage $msg)
    {
        $event = $this->queueService->dispatchConsumerEventFromPayload($msg->body);

        if ($event->getResult() === QueueConsumerResults::TEMPORARY_REJECT) {
            return static::MSG_REJECT_REQUEUE;
        } elseif ($event->getResult() === QueueConsumerResults::ACKNOWLEDGE) {
            return static::MSG_ACK;
        } elseif ($event->getResult() === QueueConsumerResults::REJECT) {
            return static::MSG_REJECT;
        } else {
            return static::MSG_SINGLE_NACK_REQUEUE;
        }
    }
}
