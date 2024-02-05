<?php

namespace Mautic\StatsBundle\Aggregate\Collection\DAO;

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
    private array $years = [];

    /**
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
     * @throws \Exception
     */
    public function getMonths(): array
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
     * @throws \Exception
     */
    public function getWeeks(): array
    {
        $flattenedWeeks = [];

        foreach ($this->getDays() as $day => $stats) {
            $week = CalculatorHelper::getWeekFromDayString($day);
            if (!isset($flattenedWeeks[$week])) {
                $flattenedWeeks[$week] = new WeekStat();
                $flattenedWeeks[$week]->setCount($stats->getSum());
            } else {
                $flattenedWeeks[$week]->addToCount($stats->getSum());
            }
        }

        ksort($flattenedWeeks);

        return $flattenedWeeks;
    }

    /**
     * @return DayStat[]
     *
     * @throws \Exception
     */
    public function getDays(): array
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
     * @throws \Exception
     */
    public function getHours(): array
    {
        $flattenedHours = [];

        $days = $this->getDays();
        foreach ($days as $dayStats) {
            $stats = $dayStats->getStats();

            foreach ($stats as $hour => $hourStat) {
                $flattenedHours[$hour] = $hourStat;
            }
        }

        ksort($flattenedHours);

        return $flattenedHours;
    }
}
