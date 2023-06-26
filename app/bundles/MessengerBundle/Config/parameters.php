<?php

use Mautic\MessengerBundle\MauticMessengerRoutes;
/** @var ContainerBuilder $container */
use Mautic\MessengerBundle\Message\EmailHitNotification;
use Mautic\MessengerBundle\Message\PageHitNotification;
use Mautic\MessengerBundle\Middleware\SynchronousExtrasMiddleware;
use Symfony\Component\DependencyInjection\ContainerBuilder;

$container->loadFromExtension('framework', [
    'messenger' => [
        'buses' => [
            'messenger.bus.default' => [
                'default_middleware'    => true,
                'middleware'    => [
                    SynchronousExtrasMiddleware::class,
                ],
            ],
        ],
        'routing'           => [
            PageHitNotification::class  => MauticMessengerRoutes::SYNC,
            EmailHitNotification::class => MauticMessengerRoutes::SYNC,
        ],
        'transports'        => [
            MauticMessengerRoutes::SYNC      => 'sync://',
        ],
    ],
]);
