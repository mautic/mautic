<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StatsBundle\Aggregate;

use Mautic\StatsBundle\Aggregate\Collection\DAO\StatDAO;
use Mautic\StatsBundle\Aggregate\Collection\DAO\StatsDAO;
use Mautic\StatsBundle\Aggregate\Helper\CalculatorHelper;

class Calculator
{
    /**
     * @var StatsDAO
     */
    private $statsDAO;

    /**
     * Calculator constructor.
     *
     * @param StatsDAO $statsDAO
     */
    public function __construct(StatsDAO $statsDAO)
    {
        $this->statsDAO = $statsDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getSumsByYear($labelFormat = 'Y')
    {
        $statDAO  = new StatDAO();
        $lastYear = null;
        foreach ($this->statsDAO->getYears() as $thisYear => $stats) {
            CalculatorHelper::fillInMissingYears($statDAO, $lastYear, $thisYear, $labelFormat);

            $statDAO->addStat(
                CalculatorHelper::getYearLabel($thisYear, $labelFormat),
                $stats->getSum()
            );

            $lastYear = $thisYear;
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getSumsByMonth($labelFormat = 'n')
    {
        $statDAO   = new StatDAO();
        $lastMonth = null;
        foreach ($this->statsDAO->getMonths() as $thisMonth => $stats) {
            CalculatorHelper::fillInMissingMonths($statDAO, $lastMonth, $thisMonth, $labelFormat);

            $statDAO->addStat(
                CalculatorHelper::getMonthLabel($thisMonth, $labelFormat),
                $stats->getSum()
            );

            $lastMonth = $thisMonth;
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getSumsByDay($labelFormat = 'j')
    {
        $statDAO   = new StatDAO();
        $yesterday = null;
        foreach ($this->statsDAO->getDays() as $today => $stats) {
            CalculatorHelper::fillInMissingDays($statDAO, $yesterday, $today, $labelFormat);

            $statDAO->addStat(
                CalculatorHelper::getDayLabel($today, $labelFormat),
                $stats->getSum()
            );

            $yesterday = $today;
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getCountsByHour($labelFormat = 'H')
    {
        $statDAO  = new StatDAO();
        $lastHour = null;
        foreach ($this->statsDAO->getHours() as $thisHour => $stats) {
            CalculatorHelper::fillInMissingHours($statDAO, $lastHour, $thisHour, $labelFormat);

            $statDAO->addStat(
                CalculatorHelper::getHourLabel($thisHour, $labelFormat),
                $stats->getCount()
            );

            $lastHour = $thisHour;
        }

        return $statDAO;
    }

    /**
     * Get per month average for each year. This assumes that a stat is injected
     * per month even if the sum is 0.
     *
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getMonthlyAveragesByYear($labelFormat = 'Y')
    {
        $statDAO = new StatDAO();
        $counts  = [];
        $sums    = [];

        foreach ($this->statsDAO->getYears() as $year => $stats) {
            $key = CalculatorHelper::getYearLabel($year, $labelFormat);
            if (!isset($counts[$key])) {
                $counts[$key] = 0;
                $sums[$key]   = 0;
            }

            $counts[$key] += $stats->getCount();
            $sums[$key] += $stats->getSum();
        }

        $lastYear = null;
        foreach ($counts as $thisYear => $count) {
            CalculatorHelper::fillInMissingYears($statDAO, $lastYear, $thisYear, $labelFormat);

            $lastYear = $thisYear;
        }

        return $statDAO;
    }

    /**
     * Get per day average for each month. This assumes that a stat is injected
     * per day even if the sum is 0.
     *
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getDailyAveragesByMonth($labelFormat = 'n')
    {
        $statDAO = new StatDAO();
        $counts  = [];
        $sums    = [];

        foreach ($this->statsDAO->getMonths() as $month => $stats) {
            $key = CalculatorHelper::getMonthLabel($month, $labelFormat);
            if (!isset($counts[$key])) {
                $counts[$key] = 0;
                $sums[$key]   = 0;
            }

            $counts[$key] += $stats->getCount();
            $sums[$key] += $stats->getSum();
        }

        $lastMonth = null;
        foreach ($counts as $thisMonth => $count) {
            CalculatorHelper::fillInMissingMonths($statDAO, $lastMonth, $thisMonth, $labelFormat);

            $statDAO->addStat(
                $thisMonth,
                $count ? $sums[$thisMonth] / $count : 0
            );

            $lastMonth = $thisMonth;
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getHourlyAveragesByDay($labelFormat = 'j')
    {
        $statDAO = new StatDAO();
        $counts  = [];
        $sums    = [];

        foreach ($this->statsDAO->getDays() as $day => $stats) {
            $key = CalculatorHelper::getDayLabel($day, $labelFormat);
            if (!isset($counts[$key])) {
                $counts[$key] = 0;
                $sums[$key]   = 0;
            }

            $counts[$key] += $stats->getCount();
            $sums[$key] += $stats->getSum();
        }

        $yesterday = null;
        foreach ($counts as $today => $count) {
            CalculatorHelper::fillInMissingDays($statDAO, $yesterday, $today, $labelFormat);

            $statDAO->addStat(
                $today,
                $count ? $sums[$today] / $count : 0
            );

            $yesterday = $today;
        }

        return $statDAO;
    }
}
