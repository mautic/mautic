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
use Symfony\Component\Validator\Constraints\NotBlank;


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
     * @var string
     */
    protected $protocolUiTranslation = 'mautic.queue.config.protocol.rabbitmq';

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

    /**
     * @param Events\QueueConfigEvent $event
     */
    public function buildConfig(Events\QueueConfigEvent $event)
    {
        $showConditions = '{"config_queueconfig_queue_protocol":["rabbitmq"]}';

        $event->addFormField(
            'rabbitmq_host',
            'text',
            [
                'label'      => 'mautic.queue.config.host',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip' => 'mautic.queue.config.host.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
                'data' => empty($options['data']['rabbitmq_host']) ? 'localhost' : $options['data']['rabbitmq_host'],
            ]
        );

        $event->addFormField(
            'rabbitmq_port',
            'text',
            [
                'label'      => 'mautic.queue.config.port',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip' => 'mautic.queue.config.port.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
                'data' => empty($options['data']['rabbitmq_port']) ? '5672' : $options['data']['rabbitmq_port'],
            ]
        );

        $event->addFormField(
            'rabbitmq_vhost',
            'text',
            [
                'label'      => 'mautic.queue.config.rabbitmq.vhost',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip' => 'mautic.queue.config.rabbitmq.vhost.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
                'data' => empty($options['data']['rabbitmq_vhost']) ? '/' : $options['data']['rabbitmq_vhost'],
            ]
        );

        $event->addFormField(
            'rabbitmq_user',
            'text',
            [
                'label'      => 'mautic.queue.config.rabbitmq.user',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip' => 'mautic.queue.config.rabbitmq.user.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
                'data' => empty($options['data']['rabbitmq_user']) ? 'guest' : $options['data']['rabbitmq_user'],
            ]
        );

        $event->addFormField(
            'rabbitmq_password',
            'password',
            [
                'label'      => 'mautic.queue.config.rabbitmq.password',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'placeholder'  => 'mautic.user.user.form.passwordplaceholder',
                    'preaddon'     => 'fa fa-lock',
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.rabbitmq.password.tooltip',
                    'autocomplete' => 'off',
                ],
                'data' => empty($options['data']['rabbitmq_password']) ? 'guest' : $options['data']['rabbitmq_password'],
            ]
        );
    }
}
