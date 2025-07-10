<?php

namespace Mautic\StatsBundle\Aggregate\Collection\Stats;

class WeekStat
{
    private int $count = 0;

    public function getCount(): int
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
