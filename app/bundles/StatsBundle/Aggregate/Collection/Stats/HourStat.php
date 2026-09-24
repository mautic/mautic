<?php

declare(strict_types=1);

namespace Mautic\StatsBundle\Aggregate\Collection\Stats;

final class HourStat
{
    private int $count = 0;

    /**
     * @param string $hour "2018-12-07 12" format
     */
    public function __construct(
        private readonly string $hour,
    ) {
    }

    public function getHour(): string
    {
        return $this->hour;
    }

    public function setCount(int $count): void
    {
        $this->count = (int) $count;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
