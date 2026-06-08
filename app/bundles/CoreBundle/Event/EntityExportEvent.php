<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class EntityExportEvent extends Event
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $entities     = [];
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $dependencies = [];

    public function __construct(private string $entityName, private int $entityId)
    {
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * Add a single entity to the collection.
     *
     * @param array<string, mixed> $entity
     */
    public function addEntity(string $entityName, array $entity): void
    {
        $this->entities[$entityName][] = $entity;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * Add multiple entities to the collection.
     *
     * @param array<string, array<string, mixed>> $entities
     */
    public function addEntities(array $entities): void
    {
        $this->entities = array_merge($this->entities, $entities);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Add a single entity to the dependencies.
     *
     * @param array<string, mixed> $entity
     */
    public function addDependencyEntity(string $entityName, array $entity): void
    {
        $this->dependencies[$entityName][] = $entity;
    }

    /**
     * Add multiple entities to the dependencies.
     *
     * @param array<string, array<string, mixed>> $entities
     */
    public function addDependencies(array $entities): void
    {
        foreach ($entities as $entityName => $entityList) {
            foreach ($entityList as $entity) {
                $this->addDependencyEntity($entityName, $entity);
            }
        }
    }
}
