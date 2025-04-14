<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class EntityImportEvent extends Event
{
    /**
     * @var array<int, int>
     */
    private array $idMap = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $dependencies = [];

    public const UPDATE = 'update';
    public const NEW    = 'new';
    public const ERRORS = 'errors';

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $status = [
        self::UPDATE => [],
        self::NEW    => [],
        self::ERRORS => [],
    ];

    /** @phpstan-ignore-next-line */
    public function __construct(private string $entityName, private array $data, private ?int $userId)
    {
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * @return mixed[]
     */
    public function getEntityData(): array
    {
        return $this->data;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function addEntityIdMap(int $originalId, int $newId): void
    {
        $this->idMap[$originalId] = $newId;
    }

    /**
     * @return array<int, int>
     */
    public function getEntityIdMap(): array
    {
        return $this->idMap;
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
        $this->dependencies = array_merge($this->dependencies, $entities);
    }

    /**
     * @param array<string, mixed> $value
     */
    public function setStatus(string $key, array $value): void
    {
        $this->status[$key] = $value;
    }

    /**
     * @return mixed|null
     */
    public function getStatus(): mixed
    {
        return $this->status ?? null;
    }
}
