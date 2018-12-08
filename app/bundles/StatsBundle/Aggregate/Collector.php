<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StatsBundle\Aggregate;

use Mautic\StatsBundle\Aggregate\Collection\StatCollection;
use Mautic\StatsBundle\Event\AggregateStatRequestEvent;
use Mautic\StatsBundle\Event\Options\EventOptions;
use Mautic\StatsBundle\StatEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Collector.
 */
class Collector
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Collector constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string            $statName
     * @param \DateTime         $fromDateTime
     * @param \DateTime         $toDateTime
     * @param EventOptions|null $eventOptions
     *
     * @return StatCollection
     */
    public function fetchStats($statName, \DateTime $fromDateTime, \DateTime $toDateTime, EventOptions $eventOptions = null)
    {
        if (null === $eventOptions) {
            $eventOptions = new EventOptions();
        }

        $event = new AggregateStatRequestEvent($statName, $fromDateTime, $toDateTime, $eventOptions);

        $this->eventDispatcher->dispatch(StatEvents::AGGREGATE_STAT_REQUEST, $event);

        return $event->getStatCollection();
    }
}
