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

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\QueueBundle\Event as Events;
use Mautic\QueueBundle\Model\RabbitMqConsumer;
use Mautic\QueueBundle\Model\RabbitMqProducer;
use Mautic\QueueBundle\Queue\QueueProtocol;
use Mautic\QueueBundle\QueueEvents;
use OldSound\RabbitMqBundle\RabbitMq\Consumer;


/**
 * Class RabbitMqSubscriber
 */
class RabbitMqSubscriber extends CommonSubscriber
{
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
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            QueueEvents::PUBLISH_MESSAGE => ['onPublishMessage', 0],
            QueueEvents::CONSUME_MESSAGE => ['onConsumeMessage', 0],
        ];
    }

    /**
     * @param Events\QueueEvent $event
     */
    public function onPublishMessage(Events\QueueEvent $event)
    {
        if (!$event->checkContext(QueueProtocol::RABBITMQ)) {
            return;
        }

        $this->producer->setQueue($event->getQueueName());
        $this->producer->publish(serialize($event->getPayload()), $event->getQueueName());
    }

    /**
     * @param Events\QueueEvent $event
     */
    public function onConsumeMessage(Events\QueueEvent $event)
    {
        if (!$event->checkContext(QueueProtocol::RABBITMQ)) {
            return;
        }

        $this->consumer->setQueueOptions(['name' => $event->getQueueName()]);
        $this->consumer->setRoutingKey($event->getQueueName());
        $this->consumer->consume($event->getMessages());
    }
}
