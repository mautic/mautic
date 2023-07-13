<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle;

use Mautic\MessengerBundle\Message\EmailHitNotification;
use Mautic\MessengerBundle\Message\PageHitNotification;
use Mautic\MessengerBundle\Middleware\SynchronousExtrasMiddleware;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticMessengerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->loadFromExtension('framework', [
            'messenger' => [
                'buses' => [
                    'messenger.bus.hit' => [
                        'default_middleware'    => true,
                        'middleware'            => [
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
    }
}
