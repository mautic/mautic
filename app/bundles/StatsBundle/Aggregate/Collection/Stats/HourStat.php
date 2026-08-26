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
        private string $hour,
    ) {
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
    public function setCount($count): void
    {
        $this->count = (int) $count;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
