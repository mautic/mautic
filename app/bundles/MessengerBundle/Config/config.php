<?php

declare(strict_types=1);

use Mautic\MessengerBundle\MessageHandler\EmailHitNotificationHandler;
use Mautic\MessengerBundle\MessageHandler\PageHitNotificationHandler;
use Mautic\MessengerBundle\Middleware\SynchronousExtrasMiddleware;

return [
    'services'   => [
        'other' => [
            PageHitNotificationHandler::class  => [
                'class'     => PageHitNotificationHandler::class,
                'tag'       => 'messenger.message_handler',
                'tagArguments'  => ['bus' => 'messenger.bus.hit'],
                'arguments' => [
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
                'class' => SynchronousExtrasMiddleware::class,
                'arguments' => [
                    'messenger.senders_locator',
                ]
            ]
        ],
    ],
];
