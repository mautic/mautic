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

class MonthStat implements StatInterface
{
    /**
     * @var DayStat[]
     */
    private $stats = [];

    /**
     * @var string
     */
    private $month;

    /**
     * MonthStat constructor.
     *
     * @param string $month "2019-01" format
     */
    public function __construct($month)
    {
        $this->month = $month;
    }

    /**
     * @param int $day
     *
     * @return DayStat
     *
     * @throws \Exception
     */
    public function getDay($day)
    {
        $key = (new \DateTime("{$this->month}-$day 00:00:00"))->format('Y-m-d');

        if (!isset($this->stats[$key])) {
            $this->stats[$key] = new DayStat($key);
        }

        return $this->stats[$key];
    }

    /**
     * @return DayStat[]
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
