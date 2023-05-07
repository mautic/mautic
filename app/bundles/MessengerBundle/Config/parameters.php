<?php

/** @var ContainerBuilder $container */
use Mautic\MessengerBundle\MauticMessengerRoutes;
/** @var ContainerBuilder $container */
use Mautic\MessengerBundle\Message\EmailHitNotification;
use Mautic\MessengerBundle\Message\PageHitNotification;
use Mautic\MessengerBundle\Messenger\FailedTransportMiddleware;
use Symfony\Component\DependencyInjection\ContainerBuilder;

$container->loadFromExtension('framework', [
    'messenger' => [
        'buses' => [
            'messenger.bus.default' => [
                'middleware' => [
                    FailedTransportMiddleware::class,
                ],
            ],
        ],
        'failure_transport' => 'failed',
        'routing'           => [
            PageHitNotification::class  => MauticMessengerRoutes::SYNC,
            EmailHitNotification::class => MauticMessengerRoutes::SYNC,
        ],
        'transports'        => [
            MauticMessengerRoutes::SYNC      => 'sync://',
            'failed'                         => [
                'dsn' => 'doctrine://default?queue_name=failed',
            ],
        ],
    ],
]);
