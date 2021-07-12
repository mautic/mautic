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

use Mautic\StatsBundle\Aggregate\Collection\Stats\WeekStat;
use PHPUnit\Framework\TestCase;

class WeekStatTest extends TestCase
{
    public function testAll()
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
