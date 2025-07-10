<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\Helper\StatsCollectionHelper;
use Mautic\StatsBundle\Event\AggregateStatRequestEvent;
use Mautic\StatsBundle\StatEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GraphAggregateStatsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private StatsCollectionHelper $statsCollectionHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StatEvents::AGGREGATE_STAT_REQUEST => ['onStatRequest', 0],
        ];
    }

    public function onStatRequest(AggregateStatRequestEvent $event): void
    {
        if (!$event->checkContextPrefix(StatsCollectionHelper::GENERAL_STAT_PREFIX.'-')) {
            return;
        }

        $this->statsCollectionHelper->generateStats(
            $event->getStatName(),
            $event->getFromDateTime(),
            $event->getToDateTime(),
            $event->getOptions(),
            $event->getStatCollection()
        );

        $event->statsCollected();
    }
}
