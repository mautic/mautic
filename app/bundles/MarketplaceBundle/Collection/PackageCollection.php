<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Collection;

use Mautic\MarketplaceBundle\DTO\Package;
use Mautic\MarketplaceBundle\Exception\RecordNotFoundException;

class PackageCollection implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * @var Package[]
     */
    private $records;

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @param Package[] $records
     */
    public function __construct(array $records = [])
    {
        $this->records = array_values($records);
    }

    public static function fromArray(array $array): PackageCollection
    {
        return new self(
            array_map(
                fn (array $record) => Package::fromArray($record),
                $array
            )
        );
    }

    public function map(callable $callback): PackageCollection
    {
        return new self(array_map($callback, $this->records));
    }

    public function add(Package $record): void
    {
        $this->records[] = $record;
    }

    public function filter(callable $callback): PackageCollection
    {
        return new self(array_values(array_filter($this->records, $callback)));
    }

    public function current(): Package
    {
        return $this->records[$this->position];
    }

    public function next()
    {
        ++$this->position;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return isset($this->records[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function count(): int
    {
        return count($this->records);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->records[] = $value;
        } else {
            $this->records[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->records[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->records[$offset]);
    }

    public function offsetGet($offset): Package
    {
        if (isset($this->records[$offset])) {
            return $this->records[$offset];
        }

        throw new RecordNotFoundException("Package on offset {$offset} was not found");
    }
}
