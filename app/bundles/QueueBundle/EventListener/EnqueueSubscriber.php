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

use Enqueue\Client\ProducerInterface;
use Mautic\QueueBundle\Event as Events;
use Mautic\QueueBundle\Helper\EnqueueConsumer;
use Mautic\QueueBundle\Helper\EnqueueProcessor;
use Mautic\QueueBundle\Queue\QueueProtocol;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Class EnqueueSubscriber.
 */
class EnqueueSubscriber extends AbstractQueueSubscriber
{
    /**
     * @var string
     */
    protected $protocol = QueueProtocol::ENQUEUE;

    /**
     * @var string
     */
    protected $protocolUiTranslation = 'mautic.queue.config.protocol.enqueue';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * RabbitMqSubscriber constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Events\QueueEvent $event
     */
    public function publishMessage(Events\QueueEvent $event)
    {
        $this->getProducer()->sendEvent(EnqueueProcessor::TOPIC, $event->getPayload());
    }

    /**
     * @param Events\QueueEvent $event
     */
    public function consumeMessage(Events\QueueEvent $event)
    {
        $this->getConsumer()->consume($event->getMessages());
    }

    /**
     * @param Events\QueueConfigEvent $event
     */
    public function buildConfig(Events\QueueConfigEvent $event)
    {
        $options        = $event->getOptions();
        $showConditions = '{"config_queueconfig_queue_protocol":["enqueue"]}';

        $event->addFormField(
            'enqueue_dsn',
            'text',
            [
                'label'      => 'mautic.queue.config.enqueue_dsn',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.enqueue_dsn.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
                'data' => $options['data']['enqueue_dsn'],
            ]
        );

        $event->addFormField(
            'enqueue_client_prefix',
            'text',
            [
                'label'      => 'mautic.queue.config.enqueue_client_prefix',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.enqueue_client_prefix.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
                'data' => $options['data']['enqueue_client_prefix'],
            ]
        );

        $event->addFormField(
            'enqueue_client_app_name',
            'text',
            [
                'label'      => 'mautic.queue.config.enqueue_client_app_name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.enqueue_client_app_name.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
                'data' => $options['data']['enqueue_client_app_name'],
            ]
        );

        $event->addFormField(
            'enqueue_client_router_topic',
            'text',
            [
                'label'      => 'mautic.queue.config.enqueue_client_router_topic',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.enqueue_client_router_topic.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
                'data' => $options['data']['enqueue_client_router_topic'],
            ]
        );

        $event->addFormField(
            'enqueue_client_router_queue',
            'text',
            [
                'label'      => 'mautic.queue.config.enqueue_client_router_queue',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.enqueue_client_router_queue.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
                'data' => $options['data']['enqueue_client_router_queue'],
            ]
        );

        $event->addFormField(
            'enqueue_client_default_processor_queue',
            'text',
            [
                'label'      => 'mautic.queue.config.enqueue_client_default_processor_queue',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.enqueue_client_default_processor_queue.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
                'data' => $options['data']['enqueue_client_default_processor_queue'],
            ]
        );

        $event->addFormField(
            'enqueue_client_redelivered_delay_time',
            'integer',
            [
                'label'      => 'mautic.queue.config.enqueue_client_redelivered_delay_time',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.enqueue_client_redelivered_delay_time.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                    new Range(
                        [
                            'min' => 0,
                        ]
                    ),
                ],
                'data' => $options['data']['enqueue_client_redelivered_delay_time'],
            ]
        );

        $event->addFormField(
            'enqueue_consumption_idle_timeout',
            'integer',
            [
                'label'      => 'mautic.queue.config.enqueue_consumption_idle_timeout',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.enqueue_consumption_idle_timeout.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                    new Range(
                        [
                            'min' => 0,
                        ]
                    ),
                ],
                'data' => $options['data']['enqueue_consumption_idle_timeout'],
            ]
        );

        $event->addFormField(
            'enqueue_consumption_receive_timeout',
            'integer',
            [
                'label'      => 'mautic.queue.config.enqueue_consumption_receive_timeout',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.enqueue_consumption_receive_timeout.tooltip',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                    new Range(
                        [
                            'min' => 10,
                        ]
                    ),
                ],
                'data' => $options['data']['enqueue_consumption_receive_timeout'],
            ]
        );

        $event->addFormField(
            'enqueue_doctrine_ping_connection_extension',
            'yesno_button_group',
            [
                'label'     => 'mautic.queue.config.enqueue_doctrine_ping_connection_extension.enabled',
                'data'      => (bool) $options['data']['enqueue_doctrine_ping_connection_extension'],
                'yes_value' => true,
                'no_value'  => false,
                'attr'      => [
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.enqueue_doctrine_ping_connection_extension.enabled.tooltip',
                ],
        ]);

        $event->addFormField(
            'enqueue_doctrine_clear_identity_map_extension',
            'yesno_button_group',
            [
                'label'     => 'mautic.queue.config.enqueue_doctrine_clear_identity_map_extension.enabled',
                'data'      => (bool) $options['data']['enqueue_doctrine_clear_identity_map_extension'],
                'yes_value' => true,
                'no_value'  => false,
                'attr'      => [
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.enqueue_doctrine_clear_identity_map_extension.enabled.tooltip',
                ],
        ]);

        $event->addFormField(
            'enqueue_signal_extension',
            'yesno_button_group',
            [
                'label'     => 'mautic.queue.config.enqueue_signal_extension.enabled',
                'data'      => (bool) $options['data']['enqueue_signal_extension'],
                'yes_value' => true,
                'no_value'  => false,
                'attr'      => [
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.enqueue_signal_extension.enabled.tooltip',
                ],
        ]);

        $event->addFormField(
            'enqueue_reply_extension',
            'yesno_button_group',
            [
                'label'     => 'mautic.queue.config.enqueue_reply_extension.enabled',
                'data'      => (bool) $options['data']['enqueue_reply_extension'],
                'yes_value' => true,
                'no_value'  => false,
                'attr'      => [
                    'data-show-on' => $showConditions,
                    'tooltip'      => 'mautic.queue.config.enqueue_reply_extension.enabled.tooltip',
                ],
        ]);
    }

    /**
     * @return ProducerInterface
     */
    public function getProducer()
    {
        return $this->container->get('enqueue.producer');
    }

    /**
     * @return EnqueueConsumer
     */
    public function getConsumer()
    {
        return $this->container->get('mautic.queue.helper.enqueue_consumer');
    }
}
