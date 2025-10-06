<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched to allow bundles to extend entity type to model key mappings.
 */
final class EntityTypeModelMappingEvent extends Event
{
    /**
     * @param array<string, string> $mappings
     */
    public function __construct(private array $mappings = [])
    {
    }

    /**
     * Add a model key mapping.
     */
    public function addMapping(string $entityType, string $modelKey): void
    {
        $this->mappings[$entityType] = $modelKey;
    }

    /**
     * Add multiple model key mappings.
     *
     * @param array<string, string> $mappings
     */
    public function addMappings(array $mappings): void
    {
        $this->mappings = array_merge($this->mappings, $mappings);
    }

    /**
     * Get all model key mappings.
     *
     * @return array<string, string>
     */
    public function getMappings(): array
    {
        return $this->mappings;
    }

    /**
     * Get model key for entity type or return entity type if no mapping exists.
     */
    public function getModelKey(string $entityType): string
    {
        return $this->mappings[$entityType] ?? $entityType;
    }
}
