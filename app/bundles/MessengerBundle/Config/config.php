<?php

return [
    'services' => [
        'forms' => [
            'mautic.form.type.messengerconfig' => [
                'class'     => \Mautic\MessengerBundle\Form\Type\ConfigType::class,
                'arguments' => [
                    'translator',
                    'mautic.messenger.transport_type',
                ],
            ],
        ],
        'events' => [
            'mautic.messenger.config.subscriber' => [
                'class'     => \Mautic\MessengerBundle\EventListener\ConfigSubscriber::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                    'mautic.messenger.transport_type',
                ],
            ],
        ],
        'models' => [
            'mautic.messenger.transport_type' => [
                'class'     => \Mautic\MessengerBundle\Model\MessengerTransportType::class,
                'arguments' => [],
            ],
        ],
    ],

    'parameters' => [
        'messenger_type'                      => 'async', // sync means no queue, async means there is queue
        'messenger_dsn'                       => 'doctrine://default', // default is doctrine://default
        'messenger_retry_strategy_max_retries'=> 3, /// Maximum number of retries for a failed send
        'messenger_retry_strategy_delay'      => 1000, /// Delay in milliseconds between retries
        'messenger_retry_strategy_multiplier' => 2, /// Delay multiplier between retries  e.g. 1 second delay, 2 seconds, 4 seconds
        'messenger_retry_strategy_max_delay'  => 0, /// maximum delay in milliseconds between retries
    ],
];
