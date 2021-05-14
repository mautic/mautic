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

use Mautic\StatsBundle\Aggregate\Collection\Stats\HourStat;
use PHPUnit\Framework\TestCase;

class HourStatTest extends TestCase
{
    public function testAll()
    {
        $hour     = '2018-12-07 12';
        $hourStat = new HourStat('2018-12-07 12');
        $this->assertSame($hour, $hourStat->getHour());

        // Counts
        $this->assertSame(0, $hourStat->getCount());
        $count = 1;
        $hourStat->setCount($count);
        $this->assertSame($count, $hourStat->getCount());
        $count = 2;
        $hourStat->setCount($count);
        $this->assertSame($count, $hourStat->getCount());
    }
}
