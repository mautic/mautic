<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Collection;

use Mautic\MarketplaceBundle\DTO\Review;

final class ReviewCollection implements \Iterator, \Countable
{
    /**
     * @var Review[]
     */
    private readonly array $records;

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
        // Convert object format to indexed array
        $array = array_values($array);

        return new self(
            array_map(
                Review::fromArray(...),
                array_filter($array, is_array(...))
            )
        );
    }

    public function getAverageRating(): float
    {
        $count = $this->count();

        if (0 === $count) {
            return 0;
        }

        $total = array_reduce($this->records, fn (int $carry, Review $review): int => $carry + $review->rating, 0);

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
}
