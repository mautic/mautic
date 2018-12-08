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

use Mautic\StatsBundle\Aggregate\Calculator;
use Mautic\StatsBundle\Aggregate\Collection\DAO\StatsDAO;

class CalculatorTest extends \PHPUnit_Framework_TestCase
{
    public function testSumByYearReturnsExpectedCount()
    {
        $expected = [
            2018 => 600,
            2019 => 300,
        ];

        $this->assertEquals($expected, $this->getCalculator()->getSumsByYear()->getStats());
    }

    public function testSumByMonthReturnsExpectedCount()
    {
        $expected = [
            11 => 200,
            12 => 700,
        ];

        $this->assertEquals($expected, $this->getCalculator()->getSumsByMonth()->getStats());

        $expected = [
            '2018-12' => 600,
            '2019-11' => 200,
            '2019-12' => 100,
        ];

        $this->assertEquals($expected, $this->getCalculator()->getSumsByMonth('Y-m')->getStats());
    }

    public function testSumByDayReturnsExpectedCount()
    {
        $expected = [
            7 => 500,
            8 => 400,
        ];

        $this->assertEquals($expected, $this->getCalculator()->getSumsByDay()->getStats());

        $expected = [
            '2018-12-07' => 300,
            '2018-12-08' => 300,
            '2019-11-07' => 100,
            '2019-11-08' => 100,
            '2019-12-07' => 100,
        ];

        $this->assertEquals($expected, $this->getCalculator()->getSumsByDay('Y-m-d')->getStats());
    }

    public function testMonthlyAverageByYearReturnsExpectedCount()
    {
        $expected = [
            2018 => 600, // 600 / 1 month (12/19)
            2019 => 150, // 300 / 2 months (11/19, 12/19)
        ];

        $this->assertEquals($expected, $this->getCalculator()->getMonthlyAveragesByYear()->getStats());
    }

    public function testDailyAverageByMonthReturnsExpectedCount()
    {
        $expected = [
            11 => 100, // 200 / 2 days (11/7/2018, 11/8/2018)
            12 => 233.3333333333, // 700 / 3 days (12/7/2018, 12/8/2018, 12/7/2019)
        ];

        $this->assertEquals($expected, $this->getCalculator()->getDailyAveragesByMonth()->getStats());

        $expected = [
            '2018-12' => 300, // 600 / 2 days (12/07/18, 12/07/18)
            '2019-11' => 100, // 200 / 2 days (11/07/19, 11/08/19)
            '2019-12' => 100, // 100 / 1 day (12/07/19)
        ];

        $this->assertEquals($expected, $this->getCalculator()->getDailyAveragesByMonth('Y-m')->getStats());
    }

    public function testAverageByDayReturnsExpectedCount()
    {
        $expected = [
            7 => 125, // 500 / 4 hours (12/7/2018 12pm, 12/7/2018 1pm, 11/7/19 12pm, 12/7/19 12pm)
            8 => 200, // 400 / 2 hours (12/8/18 2pm, 11/8/19 12pm))
        ];

        $this->assertEquals($expected, $this->getCalculator()->getHourlyAveragesByDay()->getStats());

        $expected = [
            '11-07' => 100, // 100 / 1 hour (11/7/19 12pm)
            '11-08' => 100, // 100 / 1 hour (11/8/19 12pm)
            '12-07' => 133.33333333333334, // 400 / 3 hours  (12/7/18 12pm, 12/7/18 1pm, 12/7/19 12pm)
            '12-08' => 300, // 300 / 1 hour (12/8/18 2pm)
        ];

        $this->assertEquals($expected, $this->getCalculator()->getHourlyAveragesByDay('m-d')->getStats());
    }

    /**
     * @return Calculator
     *
     * @throws \Exception
     */
    private function getCalculator()
    {
        $stats = new StatsDAO();

        $stats->getYear(2018)
            ->getMonth(12)
            ->getDay(7)
            ->getHour(12)
            ->setCount(100);

        $stats->getYear(2018)
            ->getMonth(12)
            ->getDay(7)
            ->getHour(13)
            ->setCount(200);

        $stats->getYear(2018)
            ->getMonth(12)
            ->getDay(8)
            ->getHour(14)
            ->setCount(300);

        $stats->getYear(2019)
            ->getMonth(11)
            ->getDay(7)
            ->getHour(12)
            ->setCount(100);

        $stats->getYear(2019)
            ->getMonth(11)
            ->getDay(8)
            ->getHour(12)
            ->setCount(100);

        $stats->getYear(2019)
            ->getMonth(12)
            ->getDay(7)
            ->getHour(12)
            ->setCount(100);

        return new Calculator($stats);
    }
}
