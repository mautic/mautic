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
      'other' => [
        'mautic.helper.queue' => [
          'class'     => 'Mautic\QueueBundle\Helper\QueueHelper',
          'arguments' => 'mautic.factory',
        ],
      ],
      'helper' => [
        'mautic.rabbitMQ' => [
          'class'     => 'Mautic\QueueBundle\Model\RabbitMq',
          'arguments' => [
            'old_sound_rabbit_mq.task_email_producer',
          ],
        ],
        'mautic.task_email_service' => [
          'class'     => 'Mautic\QueueBundle\Model\TaskEmailService',
          'arguments' => [
            'mautic.helper.queue',
            'mautic.rabbitMQ',
          ],
        ],
        'email_consumer' => [
          'class'     => 'Mautic\QueueBundle\Model\RabbitmqConsumer',
          'arguments' => 'mautic.email.model.email',
        ],
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
