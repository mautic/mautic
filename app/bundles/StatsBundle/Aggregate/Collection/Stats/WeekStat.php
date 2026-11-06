<?php

declare(strict_types=1);

namespace Mautic\StatsBundle\Aggregate\Collection\Stats;

final class WeekStat
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
