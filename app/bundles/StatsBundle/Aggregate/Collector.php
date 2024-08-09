<?php

namespace Mautic\StatsBundle\Aggregate;

use Mautic\StatsBundle\Aggregate\Collection\StatCollection;
use Mautic\StatsBundle\Event\AggregateStatRequestEvent;
use Mautic\StatsBundle\Event\Options\FetchOptions;
use Mautic\StatsBundle\StatEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Collector
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @param string $statName
     *
     * @return StatCollection
     */
    public function fetchStats($statName, \DateTime $fromDateTime, \DateTime $toDateTime, FetchOptions $fetchOptions = null)
    {
        if (null === $fetchOptions) {
            $fetchOptions = new FetchOptions();
        }

        $event = new AggregateStatRequestEvent($statName, $fromDateTime, $toDateTime, $fetchOptions);

        $this->eventDispatcher->dispatch($event, StatEvents::AGGREGATE_STAT_REQUEST);

        return $event->getStatCollection();
    }
}
