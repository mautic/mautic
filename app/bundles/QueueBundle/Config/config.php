<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'services' => [
        'events' => [
            'mautic.queue.rabbitmq.subscriber' => [
                'class'     => 'Mautic\QueueBundle\EventListener\RabbitMqSubscriber',
                'arguments' => 'service_container',
            ],
            'mautic.queue.beanstalkd.subscriber' => [
                'class'     => 'Mautic\QueueBundle\EventListener\BeanstalkdSubscriber',
                'arguments' => [
                    'service_container',
                    'mautic.queue.service',
                ],
            ],
            'mautic.queue.enqueue.subscriber' => [
                'class'     => 'Mautic\QueueBundle\EventListener\EnqueueSubscriber',
                'arguments' => [
                    'service_container',
                ],
            ],
            'mautic.queue.configbundle.subscriber' => [
                'class'     => 'Mautic\QueueBundle\EventListener\ConfigSubscriber',
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
        'forms' => [
            'mautic.form.type.queueconfig' => [
                'class'     => 'Mautic\QueueBundle\Form\Type\ConfigType',
                'arguments' => 'event_dispatcher',
                'alias'     => 'queueconfig',
            ],
        ],
        'other' => [
            'mautic.queue.service' => [
                'class'     => 'Mautic\QueueBundle\Queue\QueueService',
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'event_dispatcher',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.queue.helper.rabbitmq_consumer' => [
                'class'     => 'Mautic\QueueBundle\Helper\RabbitMqConsumer',
                'arguments' => 'mautic.queue.service',
            ],
            'mautic.queue.helper.enqueue_consumer' => [
                'class'     => 'Mautic\QueueBundle\Helper\EnqueueConsumer',
                'arguments' => [
                    '@enqueue.client.queue_consumer',
                    '@enqueue.client.delegate_processor',
                    '@enqueue.client.meta.queue_meta_registry',
                    '@enqueue.client.driver',
                ],
            ],
            'mautic.queue.helper.enqueue_processor' => [
                'class'     => 'Mautic\QueueBundle\Helper\EnqueueProcessor',
                'arguments' => [
                    '@mautic.queue.service',
                ],
                'tags' => ['enqueue.client.processor'],
            ],
        ],
    ],
    'parameters' => [
        'queue_protocol'                                => '',
        'rabbitmq_host'                                 => 'localhost',
        'rabbitmq_port'                                 => '5672',
        'rabbitmq_vhost'                                => '/',
        'rabbitmq_user'                                 => 'guest',
        'rabbitmq_password'                             => 'guest',
        'beanstalkd_host'                               => 'localhost',
        'beanstalkd_port'                               => '11300',
        'beanstalkd_timeout'                            => '60',
        'enqueue_dsn'                                   => 'file:',
        'enqueue_client_prefix'                         => 'enqueue',
        'enqueue_client_app_name'                       => 'app',
        'enqueue_client_router_topic'                   => 'default',
        'enqueue_client_router_queue'                   => 'default',
        'enqueue_client_default_processor_queue'        => 'default',
        'enqueue_client_redelivered_delay_time'         => 0,
        'enqueue_consumption_idle_timeout'              => 0,
        'enqueue_consumption_receive_timeout'           => 100,
        'enqueue_doctrine_ping_connection_extension'    => false,
        'enqueue_doctrine_clear_identity_map_extension' => false,
        'enqueue_signal_extension'                      => true,
        'enqueue_reply_extension'                       => true,
    ],
];
