<?php

$container->loadFromExtension(
    'old_sound_rabbit_mq',
    [
        'connections' => [
            'default' => [
                'host'               => '%mautic.rabbitmq_host%',
                'port'               => '%mautic.rabbitmq_port%',
                'user'               => '%mautic.rabbitmq_user%',
                'password'           => '%mautic.rabbitmq_password%',
                'vhost'              => '%mautic.rabbitmq_vhost%',
                'lazy'               => true,
                'connection_timeout' => 3,
                'heartbeat'          => 2,
                'read_write_timeout' => 4,
            ],
        ],
        'producers' => [
            'mautic' => [
                'class'            => 'Mautic\QueueBundle\Helper\RabbitMqProducer',
                'connection'       => 'default',
                'exchange_options' => [
                    'name'    => 'mautic',
                    'type'    => 'direct',
                    'durable' => true,
                ],
                'queue_options' => [
                    'name'        => 'email_hit',
                    'auto_delete' => false,
                    'durable'     => true,
                ],
            ],
        ],
        'consumers' => [
            'mautic' => [
                'connection'       => 'default',
                'exchange_options' => [
                    'name'    => 'mautic',
                    'type'    => 'direct',
                    'durable' => true,
                ],
                'queue_options' => [
                    'name'        => 'email_hit',
                    'auto_delete' => false,
                    'durable'     => true,
                ],
                'callback'               => 'mautic.queue.helper.rabbitmq_consumer',
                'idle_timeout'           => '%mautic.rabbitmq_idle_timeout%',
                'idle_timeout_exit_code' => '%mautic.rabbitmq_idle_timeout_exit_code%',
            ],
        ],
    ]
);
