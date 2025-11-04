<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched to allow bundles to extend entity type normalization mappings.
 */
final class EntityTypeNormalizationEvent extends Event
{
    /**
     * @param array<string, string> $mappings
     */
    public function __construct(private array $mappings = [])
    {
    }

    /**
     * Add a normalization mapping.
     */
    public function addMapping(string $from, string $to): void
    {
        $this->mappings[$from] = $to;
    }

    /**
     * Add multiple normalization mappings.
     *
     * @param array<string, string> $mappings
     */
    public function addMappings(array $mappings): void
    {
        $this->mappings = array_merge($this->mappings, $mappings);
    }

    /**
     * Get all normalization mappings.
     *
     * @return array<string, string>
     */
    public function getMappings(): array
    {
        return $this->mappings;
    }

    /**
     * Get normalized entity type or return original if no mapping exists.
     */
    public function getNormalizedType(string $entityType): string
    {
        return $this->mappings[$entityType] ?? $entityType;
    }
}
