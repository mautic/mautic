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
use Mautic\QueueBundle\Queue\QueueProtocol;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;

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
