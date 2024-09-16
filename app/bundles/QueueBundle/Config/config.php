<?php

return [
    'services' => [
        'events' => [
            'mautic.queue.rabbitmq.subscriber' => [
                'class'     => \Mautic\QueueBundle\EventListener\RabbitMqSubscriber::class,
                'arguments' => 'service_container',
            ],
            'mautic.queue.beanstalkd.subscriber' => [
                'class'     => \Mautic\QueueBundle\EventListener\BeanstalkdSubscriber::class,
                'arguments' => [
                    'service_container',
                    'mautic.queue.service',
                ],
            ],
        ],
        'other' => [
            'mautic.queue.service' => [
                'class'     => \Mautic\QueueBundle\Queue\QueueService::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'event_dispatcher',
                    'monolog.logger.mautic',
                ],
            ],
            'mautic.queue.helper.rabbitmq_consumer' => [
                'class'     => \Mautic\QueueBundle\Helper\RabbitMqConsumer::class,
                'arguments' => 'mautic.queue.service',
            ],
        ],
    ],
    'parameters' => [
        // This is an advanced setup allowing a work queue/message broker to process page hits and email tokens outside of the web request.
        // The work queue/message broker must be configured and running outside of Mautic for this to function.
        // Currently supports rabbitmq or beanstalkd
        'queue_protocol'     => '',
        // The hostname of the RabbitMQ server
        'rabbitmq_host'      => 'localhost',
        // The port that the RabbitMQ server is listening on
        'rabbitmq_port'      => '5672',
        // The virtual host to use for this RabbitMQ server
        'rabbitmq_vhost'     => '/',
        // The username for the RabbitMQ server
        'rabbitmq_user'      => 'guest',
        // The password for the RabbitMQ server
        'rabbitmq_password'  => 'guest',
        // The number of seconds after which the queue consumer should timeout when idle
        'rabbitmq_idle_timeout' => 0,
        // The exit code to be returned when the consumer exits due to idle timeout
        'rabbitmq_idle_timeout_exit_code' => 0,
        // The hostname of the Beanstalkd server
        'beanstalkd_host'    => 'localhost',
        // The port that the Beanstalkd server is listening on
        'beanstalkd_port'    => '11300',
        // The default TTR for Beanstalkd jobs
        'beanstalkd_timeout' => '60',
    ],
];
