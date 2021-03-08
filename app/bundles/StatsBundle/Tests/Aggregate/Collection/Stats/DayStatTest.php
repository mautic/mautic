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
use Mautic\StatsBundle\Aggregate\Collection\Stats\HourStat;
use PHPUnit\Framework\TestCase;

class DayStatTest extends TestCase
{
    private $day  = '2019-11-07';
    private $hour = 11;
    private $dayStat;
    private $hourStat;

    protected function setUp(): void
    {
        $this->dayStat  = new DayStat($this->day);
        $this->hourStat = $this->dayStat->getHour($this->hour);
    }

    public function testGetHour()
    {
        $this->assertInstanceOf(HourStat::class, $this->hourStat);
        $this->assertSame("$this->day $this->hour", $this->hourStat->getHour());
        $this->assertSame(0, $this->hourStat->getCount());

        $this->hourStat = $this->dayStat->getHour($this->hour);

        $this->assertSame($this->hourStat, $this->dayStat->getHour($this->hour));
        $this->assertSame("$this->day $this->hour", $this->hourStat->getHour());
        $this->assertSame(0, $this->hourStat->getCount());
    }

    public function testGetStats()
    {
        $result = $this->dayStat->getStats();
        $this->assertSame(["$this->day $this->hour" => $this->hourStat], $result);
    }

    public function testGetSum()
    {
        $this->dayStat  = new DayStat($this->day);
        $this->dayStat->getHour($this->hour);
        $this->assertSame(1, $this->dayStat->getCount());
        $this->dayStat->getHour($this->hour);
        $this->assertSame(1, $this->dayStat->getCount());
        $this->dayStat->getHour($this->hour + 1);
        $this->assertSame(2, $this->dayStat->getCount());
    }

    public function testGetCount()
    {
        $this->dayStat  = new DayStat($this->day);
        $this->dayStat->getHour($this->hour);
        $this->assertSame(1, $this->dayStat->getCount());
        $this->dayStat->getHour($this->hour);
        $this->assertSame(1, $this->dayStat->getCount());
        $this->dayStat->getHour($this->hour + 1);
        $this->assertSame(2, $this->dayStat->getCount());
    }
}
