<?php

declare(strict_types=1);

namespace Mautic\StatsBundle\Aggregate\Collection\Stats;

final class YearStat implements StatInterface
{
    /**
     * @var MonthStat[]
     */
    private array $stats = [];

    public function __construct(
        private readonly int $year,
    ) {
    }

    /**
     * @param int $month
     *
     * @throws \Exception
     */
    public function getMonth($month): MonthStat
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
