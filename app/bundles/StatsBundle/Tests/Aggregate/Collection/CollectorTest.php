<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StatsBundle\Tests\Aggregate\Collection;

use Mautic\StatsBundle\Aggregate\Collector;
use Mautic\StatsBundle\Event\AggregateStatRequestEvent;
use Mautic\StatsBundle\StatEvents;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CollectorTest extends TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = new EventDispatcher();
    }

    public function testEventIsDispatched()
    {
        $this->eventDispatcher->addListener(
            StatEvents::AGGREGATE_STAT_REQUEST,
            function (AggregateStatRequestEvent $event) {
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
