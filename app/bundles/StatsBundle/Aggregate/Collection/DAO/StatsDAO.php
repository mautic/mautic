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
        return $this->years;
    }

    /**
     * @return MonthStat[]
     */
    public function getMonths()
    {
        $flattenedMonths = [];
        foreach ($this->years as $year => $yearStats) {
            $months = $yearStats->getStats();
            foreach ($months as $month => $monthStats) {
                $flattenedMonths["$year-$month"] = $monthStats;
            }
        }

        return $flattenedMonths;
    }

    /**
     * @return DayStat[]
     */
    public function getDays()
    {
        $flattenedDays = [];

        $months = $this->getMonths();
        foreach ($months as $month => $monthStats) {
            $stats = $monthStats->getStats();

            foreach ($stats as $day => $dayStats) {
                $flattenedDays["$month-$day"] = $dayStats;
            }
        }

        return $flattenedDays;
    }

    /**
     * @return int[]
     */
    public function getHours()
    {
        $flattenedHours = [];

        $days = $this->getDays();
        foreach ($days as $day => $dayStats) {
            $stats = $dayStats->getStats();

            foreach ($stats as $hour => $hourStat) {
                $flattenedHours["$day $hour"] = $hourStat->getCount();
            }
        }

        return $flattenedHours;
    }
}
