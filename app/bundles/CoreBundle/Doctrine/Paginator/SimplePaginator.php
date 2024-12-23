<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Paginator;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\CountWalker;
use IteratorAggregate;

/**
 * This is a fast paginator (unlike \Doctrine\ORM\Tools\Pagination\Paginator) that can handle simple queries using no joins or ManyToOne joins.
 * Do not use it if the $query uses oneToMany/ManyToMany joins or other complex parts (use \Doctrine\ORM\Tools\Pagination\Paginator instead).
 *
 * @template T
 *
 * @implements IteratorAggregate<mixed>
 */
class SimplePaginator implements \IteratorAggregate, \Countable
{
    private ?int $count = null;

    /**
     * @param Query<T> $query a Doctrine ORM query or query builder
     */
    public function __construct(
        private Query $query,
    ) {
    }

    /**
     * @return \Traversable<mixed>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->query->getResult());
    }

    public function count(): int
    {
        if (null === $this->count) {
            $this->count = $this->fetchCount();
        }

        return $this->count;
    }

    private function fetchCount(): int
    {
        $query = clone $this->query;
        $query->setFirstResult(null);
        $query->setMaxResults(null);
        $query->setParameters($this->query->getParameters());
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CountWalker::class]);

        return (int) $query->getSingleScalarResult();
    }
}
