<?php

return [
    'services'   => [
        'other' => [
            \Mautic\MessengerBundle\MessageHandler\PageHitNotificationHandler::class  => [
                'class'         => \Mautic\MessengerBundle\MessageHandler\PageHitNotificationHandler::class,
                'tag'           => 'messenger.message_handler',
                'tagArguments'  => ['bus' => 'messenger.bus.hit'],
                'arguments'     => [
                    'mautic.page.repository.page',
                    'mautic.page.repository.hit',
                    'mautic.lead.repository.lead',
                    'logger',
                    'mautic.page.repository.redirect',
                    'mautic.page.model.page',
                ],
            ],
            \Mautic\MessengerBundle\MessageHandler\EmailHitNotificationHandler::class => [
                'class'         => \Mautic\MessengerBundle\MessageHandler\EmailHitNotificationHandler::class,
                'tag'           => 'messenger.message_handler',
                'tagArguments'  => ['bus' => 'messenger.bus.hit'],
                'autoconfigure' => false,
                'arguments'     => [
                    'mautic.email.model.email',
                    'logger',
                ],
            ],
            \Mautic\MessengerBundle\Middleware\SynchronousExtrasMiddleware::class => [
                'class'     => \Mautic\MessengerBundle\Middleware\SynchronousExtrasMiddleware::class,
                'arguments' => [
                    'messenger.senders_locator',
                ],
            ],
        ],
    ],
    'parameters' => [
        'messenger_dsn_email'                  => 'sync://', // sync means no queue
        'messenger_dsn_failed'                 => null, // failed transport is optional
        'messenger_retry_strategy_max_retries' => 3, // Maximum number of retries for a failed send
        'messenger_retry_strategy_delay'       => 1000, // Delay in milliseconds between retries
        'messenger_retry_strategy_multiplier'  => 2.0, // Delay multiplier between retries  e.g. 1 second delay, 2 seconds, 4 seconds
        'messenger_retry_strategy_max_delay'   => 0, // maximum delay in milliseconds between retries
    ],
];
