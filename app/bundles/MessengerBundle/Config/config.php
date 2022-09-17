<?php

/*
 * @copyright   2022 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [

    'services' => [
        'forms' => [
            'mautic.form.type.messengerconfig' => [
                'class'     => \Mautic\MessengerBundle\Form\Type\ConfigType::class,
                'arguments' => [
                    'translator',
                    'mautic.messenger.messenger_type',
                ],
            ],
        ],
        'events' => [
            'mautic.messenger.config.subscriber' => [
                'class' => \Mautic\MessengerBundle\EventListener\ConfigSubscriber::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
        'models' => [
            'mautic.messenger.messenger_type' => [
                'class'     => \Mautic\MessengerBundle\Model\MessengerType::class,
                'arguments' => [],
            ],
        ],

    ],

    'parameters' => [
        'messenger_type'                    => 'sync', // sync means no queue, async means there is queue
        'messenger_dsn' => 'doctrine://default', // default is doctrine://default
        'messenger_retry_strategy_max_retries'=> 3, /// Maximum number of retries for a failed send
        'messenger_retry_strategy_delay'      => 1000, /// Delay in milliseconds between retries
        'messenger_retry_strategy_multiplier' => 2, /// Delay multiplier between retries  e.g. 1 second delay, 2 seconds, 4 seconds
        'messenger_retry_strategy_max_delay'  => 0, /// maximum delay in milliseconds between retries
    ],

];
