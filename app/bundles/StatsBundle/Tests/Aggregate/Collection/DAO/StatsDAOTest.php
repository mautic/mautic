<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StatsBundle\Tests\Aggregate\Collection\DAO;

use Mautic\StatsBundle\Aggregate\Collection\DAO\StatsDAO;
use Mautic\StatsBundle\Aggregate\Collection\Stats\DayStat;
use Mautic\StatsBundle\Aggregate\Collection\Stats\MonthStat;
use Mautic\StatsBundle\Aggregate\Collection\Stats\YearStat;
use PHPUnit\Framework\TestCase;

class StatsDAOTest extends TestCase
{
    public function testGetYearsReturnsYears()
    {
        $expected = [
            2018,
            2019,
        ];

        $stats = $this->getStats()->getYears();
        $this->assertEquals($expected, array_keys($stats));

        array_walk($stats, function ($stat) {
            $this->assertInstanceOf(YearStat::class, $stat);
        });
    }

    public function testGetMonthsReturnsFlattenedMonths()
    {
        $expected = [
            '2018-12',
            '2019-11',
            '2019-12',
        ];

        $stats = $this->getStats()->getMonths();
        $this->assertEquals($expected, array_keys($stats));

        array_walk($stats, function ($stat) {
            $this->assertInstanceOf(MonthStat::class, $stat);
        });
    }

    public function testGetDaysReturnsFlattenedDays()
    {
        $expected = [
            '2018-12-07',
            '2019-11-07',
            '2019-11-08',
            '2019-12-07',
        ];

        $stats = $this->getStats()->getDays();
        $this->assertEquals($expected, array_keys($stats));

        array_walk($stats, function ($stat) {
            $this->assertInstanceOf(DayStat::class, $stat);
        });
    }

    public function testGetHoursReturnsFlattenedHours()
    {
        $expected = [
            '2018-12-07 12',
            '2018-12-07 13',
            '2018-12-07 14',
            '2019-11-07 12',
            '2019-11-08 12',
            '2019-12-07 12',
        ];

        $stats = $this->getStats()->getHours();
        $this->assertEquals($expected, array_keys($stats));

        array_walk($stats, function ($stat) {
            $this->assertTrue(is_int($stat));
        });
    }

    /**
     * @return StatsDAO
     */
    private function getStats()
    {
        $stats = new StatsDAO();

        $stats->getYear(2019)
            ->getMonth(11)
            ->getDay(8)
            ->getHour(12)
            ->setCount(100);

        $stats->getYear(2018)
            ->getMonth(12)
            ->getDay(7)
            ->getHour(12)
            ->setCount(100);

        $stats->getYear(2018)
            ->getMonth(12)
            ->getDay(7)
            ->getHour(14)
            ->setCount(300);

        $stats->getYear(2018)
            ->getMonth(12)
            ->getDay(7)
            ->getHour(13)
            ->setCount(200);

        $stats->getYear(2019)
            ->getMonth(12)
            ->getDay(7)
            ->getHour(12)
            ->setCount(100);

        $stats->getYear(2019)
            ->getMonth(11)
            ->getDay(7)
            ->getHour(12)
            ->setCount(100);

        return $stats;
    }
}
