<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Collection;

use Mautic\MarketplaceBundle\DTO\Review;
use Mautic\MarketplaceBundle\Exception\RecordNotFoundException;

class ReviewCollection implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * @var Review[]
     */
    private array $records;

    private int $position = 0;

    /**
     * @param Review[] $records
     */
    public function __construct(array $records = [])
    {
        $this->records = array_values($records);
    }

    /**
     * @param mixed[] $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            array_map(
                fn (array $record) => Review::fromArray($record),
                $array
            )
        );
    }

    public function getAverageRating(): float
    {
        $count = $this->count();
    
        if ($count === 0) {
            return 0;
        }
    
        $total = array_reduce($this->records, fn($carry, $review) => $carry + $review->rating, 0);
    
        return $total / $count;
    }

    public function current(): Review
    {
        return $this->records[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->records[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function count(): int
    {
        return count($this->records);
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->records[] = $value;
        } else {
            $this->records[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->records[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->records[$offset]);
    }

    public function offsetGet($offset): Review
    {
        if (isset($this->records[$offset])) {
            return $this->records[$offset];
        }

        throw new RecordNotFoundException("Review on offset {$offset} was not found");
    }
}
