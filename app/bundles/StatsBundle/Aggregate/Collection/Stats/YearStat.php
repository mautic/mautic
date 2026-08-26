<?php

namespace Mautic\StatsBundle\Aggregate\Collection\Stats;

final class YearStat implements StatInterface
{
    /**
     * @var MonthStat[]
     */
    private array $stats = [];

    private readonly int $year;

    /**
     * @param int $year
     */
    public function __construct(int $year)
    {
        $this->year = (int) $year;
    }

    /**
     * @param int $month
     *
     * @return MonthStat
     *
     * @throws \Exception
     */
    public function getMonth($month)
    {
        $key = new \DateTime("{$this->year}-{$month}-01 00:00:00")->format('Y-m');

        $this->stats[$key] ??= new MonthStat($key);

        return $this->stats[$key];
    }

    /**
     * @return MonthStat[]
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    public function getSum(): int
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
