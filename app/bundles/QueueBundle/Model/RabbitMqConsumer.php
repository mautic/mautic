<?php

namespace Mautic\QueueBundle\Model;

use Mautic\QueueBundle\Event\QueueConsumerEvent;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\EventDispatcher;

class RabbitMqConsumer implements ConsumerInterface
{

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * RabbitMqConsumer constructor.
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(AMQPMessage $msg)
    {
        $payload = unserialize($msg->body);

        $queueName = $payload['mauticQueueName'];
        unset($payload['mauticQueueName']);
        $eventName = "mautic.queue_{$queueName}";

        $event = new QueueConsumerEvent($payload);
        $this->eventDispatcher->dispatch($eventName, $event);
        return true;
    }
}
