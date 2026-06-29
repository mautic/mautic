<?php

namespace Mautic\StatsBundle\Aggregate\Collection\DAO;

class StatDAO
{
    private array $stats = [];

    public function addStat($key, $value): static
    {
        if (!isset($this->stats[$key])) {
            $this->stats[$key] = 0;
        }

        $this->stats[$key] += $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStat($key)
    {
        if (!isset($this->stats[$key])) {
            throw new \InvalidArgumentException($key.' does not exist');
        }

        return $this->stats[$key];
    }

    public function getStats(): array
    {
        return $this->stats;
    }
}
