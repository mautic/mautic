<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl3.0.html
 */
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
            'task_email' => [
                'connection'       => 'default',
                'exchange_options' => [
                    'name' => 'task_email',
                    'type' => 'direct',
                ],
            ],
        ],
        'consumers' => [
            'task_email' => [
              'connection'       => 'default',
              'exchange_options' => [
                  'name' => 'task_email',
                  'type' => 'direct',
              ],
              'queue_options' => [
                'name' => 'task_email',
              ],
              'callback' => 'email_consumer',
            ],
        ],
    ]
);
