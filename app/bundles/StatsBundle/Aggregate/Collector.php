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
use Mautic\StatsBundle\Event\Options\FetchOptions;
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
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
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

        $this->eventDispatcher->dispatch(StatEvents::AGGREGATE_STAT_REQUEST, $event);

        return $event->getStatCollection();
    }
}
