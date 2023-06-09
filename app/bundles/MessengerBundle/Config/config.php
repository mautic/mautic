<?php

declare(strict_types=1);

use Mautic\MessengerBundle\MessageHandler\EmailHitNotificationHandler;
use Mautic\MessengerBundle\MessageHandler\PageHitNotificationHandler;

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
        ],
    ],
];
