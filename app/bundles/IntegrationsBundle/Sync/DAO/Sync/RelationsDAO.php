<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync;

use Countable;
use Iterator;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\RelationDAO;

class RelationsDAO implements Iterator, Countable
{
    /**
     * @var RelationDAO[]
     */
    private $relations = [];

    /**
     * @var int
     */
    private $position = 0;

    /**
     * @param RelationDAO[] $relations
     */
    public function addRelations(array $relations): void
    {
        foreach ($relations as $relation) {
            $this->addRelation($relation);
        }
    }

    public function addRelation(RelationDAO $relation): void
    {
        $this->relations[] = $relation;
    }

    /**
     * {@inheritdoc}
     */
    public function current(): RelationDAO
    {
        return $this->relations[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return isset($this->relations[$this->position]);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->relations);
    }
}
