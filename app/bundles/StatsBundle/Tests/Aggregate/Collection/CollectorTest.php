<?php

namespace Mautic\StatsBundle\Tests\Aggregate\Collection;

use Mautic\StatsBundle\Aggregate\Collector;
use Mautic\StatsBundle\Event\AggregateStatRequestEvent;
use Mautic\StatsBundle\StatEvents;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CollectorTest extends TestCase
{
    private EventDispatcher $eventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = new EventDispatcher();
    }

    public function testEventIsDispatched(): void
    {
        $this->eventDispatcher->addListener(
            StatEvents::AGGREGATE_STAT_REQUEST,
            function (AggregateStatRequestEvent $event): void {
                $statCollection = $event->getStatCollection();

                $statCollection->addStat(2018, 12, 7, 1, 100);
                $statCollection->addStat(2018, 12, 7, 2, 110);
            }
        );

        $statCollection = $this->getCollector()->fetchStats('event-name', new \DateTime(), new \DateTime());
        $stats          = $statCollection->getStats();
        $year           = $stats->getYear(2018);

        $this->assertEquals(210, $year->getSum());
    }

    /**
     * @return Collector
     */
    private function getCollector()
    {
        return new Collector($this->eventDispatcher);
    }
}
