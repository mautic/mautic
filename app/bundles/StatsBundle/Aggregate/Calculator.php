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

    public function getSumsByBestUnit()
    {
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
        $statDAO = new StatDAO();
        foreach ($this->statsDAO->getYears() as $year => $stats) {
            $statDAO->addStat(
                $this->getYearLabel($year, $labelFormat),
                $stats->getSum()
            );
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
        $statDAO = new StatDAO();
        foreach ($this->statsDAO->getMonths() as $month => $stats) {
            $statDAO->addStat(
                $this->getMonthLabel($month, $labelFormat),
                $stats->getSum()
            );
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
        $statDAO = new StatDAO();
        foreach ($this->statsDAO->getDays() as $day => $stats) {
            $statDAO->addStat(
                $this->getDayLabel($day, $labelFormat),
                $stats->getSum()
            );
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
            $key = $this->getYearLabel($year, $labelFormat);
            if (!isset($counts[$key])) {
                $counts[$key] = 0;
                $sums[$key]   = 0;
            }

            $counts[$key] += $stats->getCount();
            $sums[$key] += $stats->getSum();
        }

        foreach ($counts as $key => $count) {
            $statDAO->addStat(
                $key,
                $count ? $sums[$key] / $count : 0
            );
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
            $key = $this->getMonthLabel($month, $labelFormat);
            if (!isset($counts[$key])) {
                $counts[$key] = 0;
                $sums[$key]   = 0;
            }

            $counts[$key] += $stats->getCount();
            $sums[$key] += $stats->getSum();
        }

        foreach ($counts as $key => $count) {
            $statDAO->addStat(
                $key,
                $count ? $sums[$key] / $count : 0
            );
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
            $key = $this->getDayLabel($day, $labelFormat);
            if (!isset($counts[$key])) {
                $counts[$key] = 0;
                $sums[$key]   = 0;
            }

            $counts[$key] += $stats->getCount();
            $sums[$key] += $stats->getSum();
        }

        foreach ($counts as $key => $count) {
            $statDAO->addStat(
                $key,
                $count ? $sums[$key] / $count : 0
            );
        }

        return $statDAO;
    }

    /**
     * @param $year
     * @param $labelFormat
     *
     * @return string
     *
     * @throws \Exception
     */
    private function getYearLabel($year, $labelFormat)
    {
        return (new \DateTime("$year-01-01 00:00:00"))->format($labelFormat);
    }

    /**
     * @param $month
     * @param $labelFormat
     *
     * @return string
     *
     * @throws \Exception
     */
    private function getMonthLabel($month, $labelFormat)
    {
        return (new \DateTime("$month-01 00:00:00"))->format($labelFormat);
    }

    /**
     * @param $day
     * @param $labelFormat
     *
     * @return string
     *
     * @throws \Exception
     */
    private function getDayLabel($day, $labelFormat)
    {
        return (new \DateTime("$day 00:00:00"))->format($labelFormat);
    }
}
