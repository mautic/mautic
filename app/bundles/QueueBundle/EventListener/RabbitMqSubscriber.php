<?php

namespace Mautic\QueueBundle\EventListener;

use Mautic\QueueBundle\Event as Events;
use Mautic\QueueBundle\Queue\QueueProtocol;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RabbitMqSubscriber extends AbstractQueueSubscriber
{
    /**
     * @var string
     */
    protected $protocol = QueueProtocol::RABBITMQ;

    /**
     * @var string
     */
    protected $protocolUiTranslation = 'mautic.queue.config.protocol.rabbitmq';

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        // The container is needed due to non-required binding of the producer & consumer
        $this->container = $container;
    }

    public function publishMessage(Events\QueueEvent $event)
    {
        $producer = $this->container->get('old_sound_rabbit_mq.mautic_producer');
        $producer->setQueue($event->getQueueName());
        $producer->publish($event->getPayload(), $event->getQueueName(), [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);
    }

    public function consumeMessage(Events\QueueEvent $event)
    {
        $consumer = $this->container->get('old_sound_rabbit_mq.mautic_consumer');
        $consumer->setQueueOptions([
            'name'        => $event->getQueueName(),
            'auto_delete' => false,
            'durable'     => true,
        ]);
        $consumer->setRoutingKey($event->getQueueName());

        // Check event for positive execution time and set on Consumer
        if (0 < ($timeout = $event->getTimeout())) {
            $consumer->setGracefulMaxExecutionDateTimeFromSecondsInTheFuture($timeout);
        }

        $consumer->consume($event->getMessages());
    }
}
