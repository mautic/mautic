<?php

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChannelSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ChannelEvents::ADD_CHANNEL => ['onAddChannel', 0],
        ];
    }

    public function onAddChannel(ChannelEvent $event)
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
