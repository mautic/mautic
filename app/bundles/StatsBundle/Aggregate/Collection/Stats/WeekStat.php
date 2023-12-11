<?php

namespace Mautic\StatsBundle\Aggregate\Collection\Stats;

class WeekStat
{
    /**
     * @var int
     */
    private $count = 0;

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
    public function setCount($count): void
    {
        $this->count = (int) $count;
    }

    /**
     * @param int $count
     */
    public function addToCount($count): void
    {
        $this->count += $count;
    }
}
