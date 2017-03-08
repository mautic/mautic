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
use Symfony\Component\DependencyInjection\ContainerInterface;


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
     * @var ContainerInterface
     */
    private $container;

    /**
     * RabbitMqSubscriber constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        // The container is needed due to non-required binding of the producer & consumer
        $this->container = $container;
    }

    /**
     * @param Events\QueueEvent $event
     */
    public function publishMessage(Events\QueueEvent $event)
    {
        $producer = $this->container->get('old_sound_rabbit_mq.mautic_producer');
        $producer->setQueue($event->getQueueName());
        $producer->publish(serialize($event->getPayload()), $event->getQueueName());
    }

    /**
     * @param Events\QueueEvent $event
     */
    public function consumeMessage(Events\QueueEvent $event)
    {
        $consumer = $this->container->get('old_sound_rabbit_mq.mautic_consumer');
        $consumer->setQueueOptions(['name' => $event->getQueueName()]);
        $consumer->setRoutingKey($event->getQueueName());
        $consumer->consume($event->getMessages());
    }
}
