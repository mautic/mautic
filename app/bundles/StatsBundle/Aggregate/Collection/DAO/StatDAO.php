<?php

namespace Mautic\StatsBundle\Aggregate\Collection\DAO;

class StatDAO
{
    /**
     * @var array
     */
    private $stats = [];

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function addStat($key, $value)
    {
        if (!isset($this->stats[$key])) {
            $this->stats[$key] = 0;
        }

        $this->stats[$key] += $value;

        return $this;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function getStat($key)
    {
        if (!isset($this->stats[$key])) {
            throw new \InvalidArgumentException($key.' does not exist');
        }

        return $this->stats[$key];
    }

    /**
     * @return array
     */
    public function getStats()
    {
        return $this->stats;
    }
}
