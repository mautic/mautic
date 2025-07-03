<?php

namespace Mautic\StatsBundle\Aggregate\Collection\Stats;

class MonthStat implements StatInterface
{
    /**
     * @var DayStat[]
     */
    private array $stats = [];

    /**
     * @param string $month "2019-01" format
     */
    public function __construct(
        private $month,
    ) {
    }

    /**
     * @param int $day
     *
     * @return DayStat
     *
     * @throws \Exception
     */
    public function getDay($day)
    {
        $key = (new \DateTime("{$this->month}-$day 00:00:00"))->format('Y-m-d');

        if (!isset($this->stats[$key])) {
            $this->stats[$key] = new DayStat($key);
        }

        return $this->stats[$key];
    }

    /**
     * @return DayStat[]
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
            $sum += $stat->getSum();
        }

        return $sum;
    }

    public function getCount(): int
    {
        return count($this->stats);
    }
}
