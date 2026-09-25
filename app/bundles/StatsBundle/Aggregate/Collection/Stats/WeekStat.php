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

    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    public function addToCount(int $count): void
    {
        $this->count += $count;
    }
}
