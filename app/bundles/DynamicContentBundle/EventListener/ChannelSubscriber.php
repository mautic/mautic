<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ChannelSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ChannelEvent::class => ['onAddChannel', 0],
        ];
    }

    public function onAddChannel(ChannelEvent $event): void
    {
        $event->addChannel(
            'dynamicContent',
            [
                ReportModel::CHANNEL_FEATURE => [
                    'table' => 'dynamic_content',
                ],
            ]
        );
    }
}
