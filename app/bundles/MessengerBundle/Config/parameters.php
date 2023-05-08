<?php

/** @var ContainerBuilder $container */
use Mautic\MessengerBundle\MauticMessengerRoutes;
/** @var ContainerBuilder $container */
use Mautic\MessengerBundle\Message\EmailHitNotification;
use Mautic\MessengerBundle\Message\PageHitNotification;
use Symfony\Component\DependencyInjection\ContainerBuilder;

$container->loadFromExtension('framework', [
    'messenger' => [
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
