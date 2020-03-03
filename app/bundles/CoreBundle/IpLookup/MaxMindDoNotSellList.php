<?php

namespace Mautic\CoreBundle\IpLookup;

class MaxMindDoNotSellList implements DoNotSellListInterface
{
    private $position = 0;

    private $list = [];

    private $count = null;

    // @todo DELETE THIS WHEN DEV IS DONE!!!!!!!!
    private $mockList = ['44.242.120.158', '2.2.2.2', '3.3.3.3', '4.4.4.4', '5.5.5.5'];

    // @todo probably rename this function to represent the full length of the list
    public function count(bool $recount = false): int
    {
        if (null === $this->count || $recount) {
            // @todo do the actual counting
            $this->count = count($this->mockList);
        }

        return $this->count;
    }

    public function loadList(int $offset = 0, int $limit = 0): bool
    {
        if (0 === $offset && 0 === $limit) {
            $this->list = $this->mockList;

            return true;
        }

        $this->list = array_slice($this->mockList, $offset, $limit);

        return boolval(count($this->list));
    }

    public function getList(): array
    {
        return $this->list;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->list[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->list[$this->position]);
    }
}
