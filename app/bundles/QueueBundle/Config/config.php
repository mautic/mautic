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
        ],
    ],
    'parameters' => [
        'queue_protocol'     => '',
        'rabbitmq_host'      => 'localhost',
        'rabbitmq_port'      => '5672',
        'rabbitmq_vhost'     => '/',
        'rabbitmq_user'      => 'guest',
        'rabbitmq_password'  => 'guest',
        'beanstalkd_host'    => 'localhost',
        'beanstalkd_port'    => '11300',
        'beanstalkd_timeout' => '60',
    ],
];
