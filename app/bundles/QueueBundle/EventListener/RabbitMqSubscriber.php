<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\EventListener;

use Mautic\QueueBundle\Event as Events;
use Mautic\QueueBundle\Model\RabbitMqConsumer;
use Mautic\QueueBundle\Model\RabbitMqProducer;
use Mautic\QueueBundle\Queue\QueueProtocol;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;


/**
 * Class RabbitMqSubscriber
 */
class RabbitMqSubscriber extends AbstractQueueSubscriber
{
    /**
     * @var string
     */
    protected $protocol = QueueProtocol::RABBITMQ;

    /**
     * @var RabbitMqProducer
     */
    private $producer;

    /**
     * @var RabbitMqConsumer
     */
    private $consumer;

    /**
     * RabbitMqSubscriber constructor.
     * @param RabbitMqProducer $producer
     * @param Consumer $consumer
     */
    public function __construct(RabbitMqProducer $producer, Consumer $consumer)
    {
        $this->producer = $producer;
        $this->consumer = $consumer;
    }

    /**
     * @param Events\QueueEvent $event
     */
    public function publishMessage(Events\QueueEvent $event)
    {
        $this->producer->setQueue($event->getQueueName());
        $this->producer->publish(serialize($event->getPayload()), $event->getQueueName());
    }

    /**
     * @param Events\QueueEvent $event
     */
    public function consumeMessage(Events\QueueEvent $event)
    {
        $this->consumer->setQueueOptions(['name' => $event->getQueueName()]);
        $this->consumer->setRoutingKey($event->getQueueName());
        $this->consumer->consume($event->getMessages());
    }
}
