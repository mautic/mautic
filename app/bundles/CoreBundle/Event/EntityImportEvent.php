<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EntityImportEvent extends Event
{
    /**
     * @var array<int, int>
     */
    private array $idMap     = [];
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $dependencies = [];

    public const IMPORT_CAMPAIGN         = 'import_campaign';
    public const IMPORT_CAMPAIGN_EVENT   = 'import_campaign_event';
    public const IMPORT_CAMPAIGN_SEGMENT = 'import_campaign_segment';
    public const IMPORT_CAMPAIGN_FORM    = 'import_campaign_form';

    public function __construct(private string $entityName, private array $data, private int $userId)
    {
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }
    public function getEntityData(): array
    {
        return $this->data;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Add a single entityIdMap.
     *
     * @param array<int, int> $entity
     */
    public function addEntityIdMap(int $originalId, int $newId): void
    {
        $this->idMap[$originalId] = $newId;
    }

    /**
     * Get IdMap.
     *
     * @return array<int, int>
     */
    public function getEntityIdMap(): array
    {
        return $this->idMap;
    }

    /**
     * Add multiple entities to the collection.
     *
     * @param array<string, array<string, mixed>> $entities
     */
    // public function addEntities(array $entities): void
    // {
    //     $this->entities = array_merge($this->entities, $entities);
    // }

    /**
     * Get dependencies.
     *
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
        $this->dependencies = array_merge($this->dependencies, $entities);
    }
}
