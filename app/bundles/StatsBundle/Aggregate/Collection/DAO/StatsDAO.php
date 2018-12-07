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

use Mautic\StatsBundle\Aggregate\Collection\Stats\DayStat;
use Mautic\StatsBundle\Aggregate\Collection\Stats\HourStat;
use Mautic\StatsBundle\Aggregate\Collection\Stats\MonthStat;
use Mautic\StatsBundle\Aggregate\Collection\Stats\YearStat;

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
            $this->years[$year] = new YearStat();
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
     * @throws \Exception
     */
    public function getMonths()
    {
        $flattenedMonths = [];
        foreach ($this->years as $year => $yearStats) {
            $months = $yearStats->getStats();
            foreach ($months as $month => $monthStats) {
                $label                   = (new \DateTime("$year-$month-01 00:00:00"))->format('Y-m');
                $flattenedMonths[$label] = $monthStats;
            }
        }

        ksort($flattenedMonths);

        return $flattenedMonths;
    }

    /**
     * @return DayStat[]
     *
     * @throws \Exception
     */
    public function getDays()
    {
        $flattenedDays = [];

        $months = $this->getMonths();
        foreach ($months as $month => $monthStats) {
            $stats = $monthStats->getStats();

            foreach ($stats as $day => $dayStats) {
                $label                 = (new \DateTime("$month-$day 00:00:00"))->format('Y-m-d');
                $flattenedDays[$label] = $dayStats;
            }
        }

        ksort($flattenedDays);

        return $flattenedDays;
    }

    /**
     * @return HourStat[]
     *
     * @throws \Exception
     */
    public function getHours()
    {
        $flattenedHours = [];

        $days = $this->getDays();
        foreach ($days as $day => $dayStats) {
            $stats = $dayStats->getStats();

            foreach ($stats as $hour => $hourStat) {
                $label                  = (new \DateTime("$day $hour:00:00"))->format('Y-m-d H');
                $flattenedHours[$label] = $hourStat->getCount();
            }
        }

        ksort($flattenedHours);

        return $flattenedHours;
    }
}
