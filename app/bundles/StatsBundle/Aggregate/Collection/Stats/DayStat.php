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

class DayStat implements StatInterface
{
    /**
     * @var HourStat[]
     */
    private $stats = [];

    /**
     * @param $hour
     *
     * @return HourStat
     */
    public function getStat($hour)
    {
        if (!isset($this->stats[$hour])) {
            $this->stats[$hour] = new HourStat();
        }

        return $this->stats[$hour];
    }

    /**
     * @return HourStat[]
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
            $sum += $stat->getCount();
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
