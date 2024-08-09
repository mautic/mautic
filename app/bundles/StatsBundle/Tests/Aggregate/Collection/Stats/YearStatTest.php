<?php

namespace Mautic\StatsBundle\Tests\Aggregate\Collection\Stats;

use Mautic\StatsBundle\Aggregate\Collection\Stats\MonthStat;
use Mautic\StatsBundle\Aggregate\Collection\Stats\YearStat;
use PHPUnit\Framework\TestCase;

class YearStatTest extends TestCase
{
    private $year     = '2019';

    private $month    = 11;

    private YearStat $yearStat;

    private $monthStat;

    protected function setUp(): void
    {
        $this->yearStat  = new YearStat($this->year);
        $this->monthStat = $this->yearStat->getMonth($this->month);
    }

    public function testGetMonth(): void
    {
        $this->assertInstanceOf(MonthStat::class, $this->monthStat);
        $month = $this->yearStat->getMonth($this->month);
        $this->assertInstanceOf(MonthStat::class, $month);
        $this->assertSame([], $month->getStats());
        $this->assertSame(1, $this->yearStat->getCount());

        $month = $this->yearStat->getMonth($this->month + 1);
        $this->assertSame([], $month->getStats());
        $this->assertSame(2, $this->yearStat->getCount());
    }

    public function testGetStats(): void
    {
        $result = $this->yearStat->getStats();
        $this->assertSame(["$this->year-$this->month" => $this->monthStat], $result);
    }

    public function testGetSum(): void
    {
        $this->yearStat  = new YearStat($this->year);
        $this->yearStat->getMonth($this->month);
        $this->assertSame(1, $this->yearStat->getCount());
        $this->yearStat->getMonth($this->month);
        $this->assertSame(1, $this->yearStat->getCount());
        $this->yearStat->getMonth($this->month + 1);
        $this->assertSame(2, $this->yearStat->getCount());
    }

    public function testGetCount(): void
    {
        $this->yearStat  = new YearStat($this->year);
        $this->yearStat->getMonth($this->month);
        $this->assertSame(1, $this->yearStat->getCount());
        $this->yearStat->getMonth($this->month);
        $this->assertSame(1, $this->yearStat->getCount());
        $this->yearStat->getMonth($this->month + 1);
        $this->assertSame(2, $this->yearStat->getCount());
    }
}
