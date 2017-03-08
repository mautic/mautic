<?php

namespace Mautic\QueueBundle\Helper;

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
        $this->queueService->dispatchConsumerEventFromPayload($msg->body);
        return true;
    }
}
