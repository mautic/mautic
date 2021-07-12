<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Collection;

use Mautic\MarketplaceBundle\DTO\Maintainer;
use Mautic\MarketplaceBundle\Exception\RecordNotFoundException;

class MaintainerCollection implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * @var Maintainer[]
     */
    private array $records;

    private int $position = 0;

    /**
     * @param Maintainer[] $records
     */
    public function __construct(array $records = [])
    {
        $this->records = array_values($records);
    }

    public static function fromArray(array $array): MaintainerCollection
    {
        return new self(
            array_map(
                fn (array $record) => Maintainer::fromArray($record),
                $array
            )
        );
    }

    public function current(): Maintainer
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

    public function offsetGet($offset): Maintainer
    {
        if (isset($this->records[$offset])) {
            return $this->records[$offset];
        }

        throw new RecordNotFoundException("Maintainer on offset {$offset} was not found");
    }
}
