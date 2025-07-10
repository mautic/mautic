<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\RelationDAO;

class RelationsDAO implements \Iterator, \Countable
{
    /**
     * @var RelationDAO[]
     */
    private array $relations = [];

    private int $position = 0;

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

    public function current(): RelationDAO
    {
        return $this->relations[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->relations[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function count(): int
    {
        return count($this->relations);
    }
}
