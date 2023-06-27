<?php

declare(strict_types=1);

use Mautic\MessengerBundle\MessageHandler\EmailHitNotificationHandler;
use Mautic\MessengerBundle\MessageHandler\PageHitNotificationHandler;
use Mautic\MessengerBundle\Middleware\SynchronousExtrasMiddleware;

return [
    'services'   => [
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
        'other' => [
            'mautic.messenger.transport_type' => [
                'class'     => \Mautic\MessengerBundle\Model\MessengerTransportType::class,
            ],
            PageHitNotificationHandler::class  => [
                'class'         => PageHitNotificationHandler::class,
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
            EmailHitNotificationHandler::class => [
                'class'         => EmailHitNotificationHandler::class,
                'tag'           => 'messenger.message_handler',
                'tagArguments'  => ['bus' => 'messenger.bus.hit'],
                'autoconfigure' => false,
                'arguments'     => [
                    'mautic.email.model.email',
                    'logger',
                ],
            ],
            SynchronousExtrasMiddleware::class => [
                'class'     => SynchronousExtrasMiddleware::class,
                'arguments' => [
                    'messenger.senders_locator',
                ],
            ],
        ],
    ],

    'parameters' => [
        'messenger_type'                      => 'async', // sync means no queue, async means there is queue
        'messenger_dsn'                       => 'doctrine://default', // default is doctrine://default
        'messenger_retry_strategy_max_retries'=> 3, // / Maximum number of retries for a failed send
        'messenger_retry_strategy_delay'      => 1000, // / Delay in milliseconds between retries
        'messenger_retry_strategy_multiplier' => 2, // / Delay multiplier between retries  e.g. 1 second delay, 2 seconds, 4 seconds
        'messenger_retry_strategy_max_delay'  => 0, // / maximum delay in milliseconds between retries
    ],
];
