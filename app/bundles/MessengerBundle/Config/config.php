<?php

declare(strict_types=1);

use Mautic\MessengerBundle\MessageHandler\EmailHitNotificationHandler;
use Mautic\MessengerBundle\MessageHandler\FailedMessageHandler;
use Mautic\MessengerBundle\MessageHandler\PageHitNotificationHandler;
use Mautic\MessengerBundle\Messenger\FailedTransportMiddleware;

return [
    'services'   => [
        'other' => [
            PageHitNotificationHandler::class  => [
                'class'     => PageHitNotificationHandler::class,
                'tag'       => 'messenger.message_handler',
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
                'class'     => EmailHitNotificationHandler::class,
                'tag'       => 'messenger.message_handler',
                'arguments' => [
                    'mautic.email.model.email',
                    'logger',
                ],
            ],
            FailedMessageHandler::class        => [
                'class'     => FailedMessageHandler::class,
                'tag'       => 'messenger.message_handler',
                'arguments' => [
                    'mautic.logger.slack',
                ],
            ],
            FailedTransportMiddleware::class   => [
                'class'     => FailedTransportMiddleware::class,
                'arguments' => [
                    'mautic.logger.slack',
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
    ],
];
