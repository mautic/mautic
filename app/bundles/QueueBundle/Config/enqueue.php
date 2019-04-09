<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl3.0.html
 */

$container->loadFromExtension(
    'enqueue',
    [
        'transport' => [
            'default' => [
                'dsn' => '%mautic.enqueue_dsn%',
            ],
        ],
        'client' => [
            'prefix'                  => '%mautic.enqueue_client_prefix%',
            'app_name'                => '%mautic.enqueue_client_app_name%',
            'router_topic'            => '%mautic.enqueue_client_router_topic%',
            'router_queue'            => '%mautic.enqueue_client_router_queue%',
            'default_processor_queue' => '%mautic.enqueue_client_default_processor_queue%',
            'redelivered_delay_time'  => '%mautic.enqueue_client_redelivered_delay_time%',
        ],
        'consumption' => [
           'idle_timeout'    => '%mautic.enqueue_consumption_idle_timeout%',
           'receive_timeout' => '%mautic.enqueue_consumption_receive_timeout%',
        ],
        'extensions' => [
            'doctrine_ping_connection_extension'    => '%mautic.enqueue_doctrine_ping_connection_extension%',
            'doctrine_clear_identity_map_extension' => '%mautic.enqueue_doctrine_clear_identity_map_extension%',
            'signal_extension'                      => '%mautic.enqueue_signal_extension%',
            'reply_extension'                       => '%mautic.enqueue_reply_extension%',
        ],
    ]
);
