<?php

namespace Mautic\StatsBundle\Aggregate\Collection\Stats;

class DayStat implements StatInterface
{
    /**
     * @var HourStat[]
     */
    private array $stats = [];

    /**
     * @param string $day "2019-11-07" format
     */
    public function __construct(
        private $day
    ) {
    }

    /**
     * @param int $hour
     *
     * @return HourStat
     *
     * @throws \Exception
     */
    public function getHour($hour)
    {
        $key = (new \DateTime("{$this->day} $hour:00:00"))->format('Y-m-d H');

        if (!isset($this->stats[$key])) {
            $this->stats[$key] = new HourStat($key);
        }

        return $this->stats[$key];
    }

    /**
     * @return HourStat[]
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
            $sum += $stat->getCount();
        }

        return $sum;
    }

    public function getCount(): int
    {
        return count($this->stats);
    }
}
