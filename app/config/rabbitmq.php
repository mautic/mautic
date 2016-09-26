<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$container->loadFromExtension(
    'old_sound_rabbit_mq',
    array(
        'connections'      => array(
            'default' => array(
                'host' => '%mautic.rabbitmq_host%',
                'port' => '%mautic.rabbitmq_port%',
                'user' => '%mautic.rabbitmq_user%',
                'password' => '%mautic.rabbitmq_password%',
                'vhost' => '%mautic.rabbitmq_vhost%',
                'lazy' => true,
                'connection_timeout' => 3,
                'heartbeat' => 2,
                'read_write_timeout' => 4,
            )
        ),
        'producers'       => array(
            'task_email' => array(
                'connection'  => 'default',
                'exchange_options' => array(
                    'name' => 'task_email',
                    'type' => 'direct'
                )
            )
        ),
        'consumers' => array(
            'task_email' => array(
              'connection' => 'default',
              'exchange_options' => array(
                  'name' => 'task_email',
                  'type' => 'direct'
              ),
              'queue_options' => array(
                'name' => 'task_email'
              ),
              'callback' => 'email_consumer'
            )
        )
    )
);
