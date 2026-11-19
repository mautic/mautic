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

    public function __construct(
        private readonly string $entityName,
        private readonly int $entityId,
    ) {
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
     * Adds data to an entity already in the collection, like a plugin adding what it stores for the entity.
     *
     * @param array<string, mixed> $data
     */
    public function addEntityData(string $entityName, int $entityId, array $data): void
    {
        foreach ($this->entities[$entityName] ?? [] as $key => $entity) {
            if ((int) ($entity['id'] ?? 0) === $entityId) {
                $this->entities[$entityName][$key] = array_merge($entity, $data);
            }
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * Add multiple entities to the collection. Entities already in it, by ID, are kept as they are.
     *
     * @param array<string, array<mixed>> $entities
     */
    public function addEntities(array $entities): void
    {
        foreach ($entities as $entityName => $entityList) {
            $existingIds = array_column($this->entities[$entityName] ?? [], 'id');

            foreach ($entityList as $entity) {
                if (isset($entity['id']) && in_array($entity['id'], $existingIds, true)) {
                    continue;
                }

                $this->entities[$entityName][] = $entity;
                if (isset($entity['id'])) {
                    $existingIds[] = $entity['id'];
                }
            }
        }
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
