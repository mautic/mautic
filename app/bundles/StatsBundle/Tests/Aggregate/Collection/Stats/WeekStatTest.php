<?php

namespace Mautic\StatsBundle\Tests\Aggregate\Collection\Stats;

use Mautic\StatsBundle\Aggregate\Collection\Stats\WeekStat;
use PHPUnit\Framework\TestCase;

class WeekStatTest extends TestCase
{
    public function testAll(): void
    {
        $weekStat = new WeekStat();
        $this->assertSame(0, $weekStat->getCount());
        $count = 1;
        $weekStat->setCount($count);
        $this->assertSame($count, $weekStat->getCount());
        $weekStat->addToCount($count);
        $this->assertSame($count * 2, $weekStat->getCount());
    }
}
