<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EntityExportEvent extends Event
{
    /**
     * @var array<string, array<string, mixed>>
     *                                          Represents the entities to be exported, where the key is the entity name and the value is an array of entities
     */
    private array $entities = [];

    public const EXPORT_CAMPAIGN         = 'campaign';
    public const EXPORT_CAMPAIGN_EVENT   = 'campaign_event';
    public const EXPORT_EMAIL            = 'email';
    public const EXPORT_CAMPAIGN_SEGMENT = 'campaign_segment';
    public const EXPORT_CAMPAIGN_FORM    = 'campaign_form';

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
     * Get all entities.
     *
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
}
