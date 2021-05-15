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

class HourStat
{
    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var string
     */
    private $hour;

    /**
     * HourStat constructor.
     *
     * @param string $hour "2018-12-07 12" format
     */
    public function __construct($hour)
    {
        $this->hour = $hour;
    }

    /**
     * @return string
     */
    public function getHour()
    {
        return $this->hour;
    }

    /**
     * @param int $count
     */
    public function setCount($count)
    {
        $this->count = (int) $count;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }
}
