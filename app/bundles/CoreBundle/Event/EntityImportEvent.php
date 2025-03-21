<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EntityImportEvent extends Event
{
    /**
     * @var array<int, int>
     */
    private array $idMap = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $dependencies = [];

    /**
     * @var array<string, mixed> stores additional arguments such as import status
     */
    private array $arguments = [];

    public const UPDATE = 'update';
    public const NEW    = 'new';
    public const ERRORS = 'errors';

    private array $status = [
        self::UPDATE => [
            // "email" => [
            //     "names" => [
            //       "test email 1"
            //     ]
            //     "count" => 1
            //     "ids" => [1]
            //   ]
        ],
        self::NEW   => [
            // "email" => [
            //     "names" => [
            //       "test email 1"
            //     ]
            //     "count" => 1
            //     "ids" => [1]
            //   ]
        ],
        self::ERRORS => [],
    ];

    public function __construct(private string $entityName, private array $data, private ?int $userId)
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

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Add a single entityIdMap.
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

    /**
     * Set an argument dynamically.
     */
    public function setStatus(string $key, array $value): void
    {
        $this->status[$key] = $value;
    }

    /**
     * Get an argument by key (returns null if not found).
     *
     * @return mixed|null
     */
    public function getStatus(): mixed
    {
        return $this->status ?? null;
    }
}
