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
     * @param $month
     *
     * @return MonthStat
     */
    public function getStat($month)
    {
        if (!isset($this->stats[$month])) {
            $this->stats[$month] = new MonthStat();
        }

        return $this->stats[$month];
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
    public function getAverage()
    {
        if (count($this->stats)) {
            return 0;
        }

        $sum = $this->getSum();

        return $sum / count($this->stats);
    }
}
