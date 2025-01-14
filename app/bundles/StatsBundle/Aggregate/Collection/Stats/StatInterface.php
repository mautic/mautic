<?php

namespace Mautic\StatsBundle\Aggregate\Collection\Stats;

interface StatInterface
{
    /**
     * @return array
     */
    public function getStats();

    /**
     * @return int
     */
    public function getSum();

    /**
     * @return int
     */
    public function getCount();
}
