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
                'arguments' => [
                    'old_sound_rabbit_mq.mautic_producer',
                    'old_sound_rabbit_mq.mautic_consumer',
                ],
            ],
        ],
        'models' => [
            'mautic.queue.model.rabbitmq_consumer' => [
                'class'     => 'Mautic\QueueBundle\Model\RabbitMqConsumer',
                'arguments' => 'event_dispatcher',
            ],
        ],
        'other' => [
            'mautic.queue.service' => [
                'class'     => 'Mautic\QueueBundle\Queue\QueueService',
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'event_dispatcher',
                ],
            ]
        ],
    ],
    'parameters' => [
        'use_queue'            => false,
        'track_mail_use_queue' => true,
        'queue_protocol'       => 'rabbitmq',
        'rabbitmq_host'        => 'localhost',
        'rabbitmq_port'        => '5672',
        'rabbitmq_vhost'       => '/',
        'rabbitmq_user'        => 'guest',
        'rabbitmq_password'    => 'guest',
    ],
];
