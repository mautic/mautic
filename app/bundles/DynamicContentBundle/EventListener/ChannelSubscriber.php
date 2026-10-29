<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ReportBundle\Model\ReportModel;

final class ChannelSubscriber
{
    #[AsEventListener(event: ChannelEvent::class)]
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
