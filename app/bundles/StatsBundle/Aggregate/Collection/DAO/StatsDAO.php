<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StatsBundle\Aggregate\Collection\DAO;

use Exception;
use Mautic\StatsBundle\Aggregate\Collection\Stats\DayStat;
use Mautic\StatsBundle\Aggregate\Collection\Stats\HourStat;
use Mautic\StatsBundle\Aggregate\Collection\Stats\MonthStat;
use Mautic\StatsBundle\Aggregate\Collection\Stats\WeekStat;
use Mautic\StatsBundle\Aggregate\Collection\Stats\YearStat;
use Mautic\StatsBundle\Aggregate\Helper\CalculatorHelper;

class StatsDAO
{
    /**
     * @var YearStat[]
     */
    private $years = [];

    /**
     * @param $year
     *
     * @return YearStat
     */
    public function getYear($year)
    {
        if (!isset($this->years[$year])) {
            $this->years[$year] = new YearStat($year);
        }

        return $this->years[$year];
    }

    /**
     * @return YearStat[]
     */
    public function getYears()
    {
        ksort($this->years);

        return $this->years;
    }

    /**
     * @return MonthStat[]
     *
     * @throws Exception
     */
    public function getMonths()
    {
        $flattenedMonths = [];
        foreach ($this->years as $yearStats) {
            $months = $yearStats->getStats();
            foreach ($months as $month => $monthStats) {
                $flattenedMonths[$month] = $monthStats;
            }
        }

        ksort($flattenedMonths);

        return $flattenedMonths;
    }

    /**
     * @return WeekStat[]
     *
     * @throws Exception
     */
    public function getWeeks()
    {
        $flattenedWeeks = [];

        foreach ($this->getDays() as $day => $stats) {
            $week = CalculatorHelper::getWeekFromDayString($day);
            if (!isset($flattenedWeeks[$week])) {
                $flattenedWeeks[$week] = new WeekStat();
                $flattenedWeeks[$week]->setCount($stats->getCount());
            } else {
                $flattenedWeeks[$week]->addToCount($stats->getCount());
            }
        }

        ksort($flattenedWeeks);

        return $flattenedWeeks;
    }

    /**
     * @return DayStat[]
     *
     * @throws Exception
     */
    public function getDays()
    {
        $flattenedDays = [];

        $months = $this->getMonths();
        foreach ($months as $monthStats) {
            $stats = $monthStats->getStats();

            foreach ($stats as $day => $dayStats) {
                $flattenedDays[$day] = $dayStats;
            }
        }

        ksort($flattenedDays);

        return $flattenedDays;
    }

    /**
     * @return HourStat[]
     *
     * @throws Exception
     */
    public function getHours()
    {
        $flattenedHours = [];

        $days = $this->getDays();
        foreach ($days as $dayStats) {
            $stats = $dayStats->getStats();

            foreach ($stats as $hour => $hourStat) {
                $flattenedHours[$hour] = $hourStat->getCount();
            }
        }

        ksort($flattenedHours);

        return $flattenedHours;
    }
}
