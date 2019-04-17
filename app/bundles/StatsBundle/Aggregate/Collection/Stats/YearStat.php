<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StatsBundle\Aggregate\Collection\Stats;

class YearStat implements StatInterface
{
    /**
     * @var MonthStat[]
     */
    private $stats = [];

    /**
     * @var int
     */
    private $year;

    /**
     * YearStat constructor.
     *
     * @param int $year
     */
    public function __construct($year)
    {
        $this->year = (int) $year;
    }

    /**
     * @param int $month
     *
     * @return MonthStat
     *
     * @throws \Exception
     */
    public function getMonth($month)
    {
        $key = (new \DateTime("{$this->year}-$month-01 00:00:00"))->format('Y-m');

        if (!isset($this->stats[$key])) {
            $this->stats[$key] = new MonthStat($key);
        }

        return $this->stats[$key];
    }

    /**
     * @return MonthStat[]
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * @return int
     */
    public function getSum()
    {
        $sum = 0;
        foreach ($this->stats as $stat) {
            $sum += $stat->getSum();
        }

        return $sum;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return count($this->stats);
    }
}
