<?php

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace Mautic\StatsBundle\Tests\Aggregate\Collection\Stats;

use Mautic\StatsBundle\Aggregate\Collection\Stats\DayStat;
use Mautic\StatsBundle\Aggregate\Collection\Stats\MonthStat;
use PHPUnit\Framework\TestCase;

class MonthStatTest extends TestCase
{
    private $month  = '2019-12';
    private $day    = 11;
    private $monthStat;
    private $dayStat;

    protected function setUp(): void
    {
        $this->monthStat = new MonthStat($this->month);
        $this->dayStat   = $this->monthStat->getDay($this->day);
    }

    public function testGetDay()
    {
        $this->assertInstanceOf(DayStat::class, $this->dayStat);
        $day = $this->monthStat->getDay($this->day);
        $this->assertInstanceOf(DayStat::class, $day);
        $this->assertSame([], $day->getStats());
        $this->assertSame(1, $this->monthStat->getCount());

        $day = $this->monthStat->getDay($this->day + 1);
        $this->assertSame([], $day->getStats());
        $this->assertSame(2, $this->monthStat->getCount());
    }

    public function testGetStats()
    {
        $result = $this->monthStat->getStats();
        $this->assertSame(["$this->month-$this->day" => $this->dayStat], $result);
    }

    public function testGetSum()
    {
        $this->monthStat  = new MonthStat($this->month);
        $this->monthStat->getDay($this->day);
        $this->assertSame(1, $this->monthStat->getCount());
        $this->monthStat->getDay($this->day);
        $this->assertSame(1, $this->monthStat->getCount());
        $this->monthStat->getDay($this->day + 1);
        $this->assertSame(2, $this->monthStat->getCount());
    }

    public function testGetCount()
    {
        $this->monthStat  = new MonthStat($this->month);
        $this->monthStat->getDay($this->day);
        $this->assertSame(1, $this->monthStat->getCount());
        $this->monthStat->getDay($this->day);
        $this->assertSame(1, $this->monthStat->getCount());
        $this->monthStat->getDay($this->day + 1);
        $this->assertSame(2, $this->monthStat->getCount());
    }
}
