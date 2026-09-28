<?php

declare(strict_types=1);

namespace Mautic\StatsBundle\Aggregate\Collection\Stats;

interface StatInterface
{
    public function getStats(): array;

    /**
     * @return int
     */
    public function getSum();

    /**
     * @return int
     */
    public function getCount();
}
