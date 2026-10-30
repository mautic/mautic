<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class ChannelSubscriber
{
    #[AsEventListener(
        event: ChannelEvent::class,
    )]
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
