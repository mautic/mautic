<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StatsBundle\Aggregate\Collection\Stats;

class WeekStat
{
    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var string
     */
    private $week;

    /**
     * @param string $week
     */
    public function __construct($week)
    {
        $this->week = $week;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     */
    public function setCount($count)
    {
        $this->count = (int) $count;
    }

    /**
     * @param $count
     */
    public function addToCount($count)
    {
        $this->count += $count;
    }
}
