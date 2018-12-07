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
     * @var string
     */
    private $day;

    /**
     * DayStat constructor.
     *
     * @param string $day
     */
    public function __construct($day)
    {
        $this->day = $day;
    }

    /**
     * @param $hour
     *
     * @return HourStat
     *
     * @throws \Exception
     */
    public function getHour($hour)
    {
        $key = (new \DateTime("{$this->day} $hour:00:00"))->format('Y-m-d H');

        if (!isset($this->stats[$key])) {
            $this->stats[$key] = new HourStat($key);
        }

        return $this->stats[$key];
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
        if (count($this->stats) === 0) {
            return 0;
        }

        $sum = $this->getSum();

        return $sum / count($this->stats);
    }
}
