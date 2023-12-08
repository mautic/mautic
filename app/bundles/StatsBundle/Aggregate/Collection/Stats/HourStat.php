<?php

namespace Mautic\StatsBundle\Aggregate\Collection\Stats;

class HourStat
{
    /**
     * @var int
     */
    private $count = 0;

    /**
     * @param string $hour "2018-12-07 12" format
     */
    public function __construct(
        private $hour
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

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }
}
