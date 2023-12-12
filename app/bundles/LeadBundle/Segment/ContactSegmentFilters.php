<?php

namespace Mautic\LeadBundle\Segment;

/**
 * Array object containing filters.
 */
class ContactSegmentFilters implements \Iterator, \Countable
{
    private int $position = 0;

    /**
     * @var array|ContactSegmentFilter[]
     */
    private array $contactSegmentFilters = [];

    /**
     * @return $this
     */
    public function addContactSegmentFilter(ContactSegmentFilter $contactSegmentFilter)
    {
        $this->contactSegmentFilters[] = $contactSegmentFilter;

        return $this;
    }

    /**
     * Return the current element.
     *
     * @see  http://php.net/manual/en/iterator.current.php
     *
     * @return ContactSegmentFilter
     */
    public function current(): mixed
    {
        return $this->contactSegmentFilters[$this->position];
    }

    /**
     * Move forward to next element.
     *
     * @see  http://php.net/manual/en/iterator.next.php
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Return the key of the current element.
     *
     * @see  http://php.net/manual/en/iterator.key.php
     *
     * @return int
     */
    public function key(): mixed
    {
        return $this->position;
    }

    /**
     * Checks if current position is valid.
     *
     * @see  http://php.net/manual/en/iterator.valid.php
     */
    public function valid(): bool
    {
        return isset($this->contactSegmentFilters[$this->position]);
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @see  http://php.net/manual/en/iterator.rewind.php
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Count elements of an object.
     *
     * @see  http://php.net/manual/en/countable.count.php
     */
    public function count(): int
    {
        return count($this->contactSegmentFilters);
    }
}
