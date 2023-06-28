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
//            'mautic.messenger.config.subscriber' => [
//                'class'     => \Mautic\MessengerBundle\EventListener\ConfigSubscriber::class,
//                'arguments' => [
//                    'mautic.helper.core_parameters',
//                    'mautic.messenger.transport_type',
//                ],
//            ],
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
];
