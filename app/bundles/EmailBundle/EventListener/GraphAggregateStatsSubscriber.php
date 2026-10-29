<?php

namespace Mautic\EmailBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\EmailBundle\Helper\StatsCollectionHelper;
use Mautic\StatsBundle\Event\AggregateStatRequestEvent;

final readonly class GraphAggregateStatsSubscriber
{
    public function __construct(
        private StatsCollectionHelper $statsCollectionHelper,
    ) {
    }

    #[AsEventListener(event: AggregateStatRequestEvent::class)]
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
