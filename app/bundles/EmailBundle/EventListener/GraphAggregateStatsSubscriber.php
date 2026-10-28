<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\Helper\StatsCollectionHelper;
use Mautic\StatsBundle\Event\AggregateStatRequestEvent;

final readonly class GraphAggregateStatsSubscriber
{
    public function __construct(
        private StatsCollectionHelper $statsCollectionHelper,
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: AggregateStatRequestEvent::class,
        priority: 0
    )]
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
