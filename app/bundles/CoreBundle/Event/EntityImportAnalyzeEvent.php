<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EntityImportAnalyzeEvent extends Event
{
    /**
     * @var array<string, mixed>
     */
    private array $summary = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private string $entityName, private array $data)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getEntityData(): array
    {
        return $this->data;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * Set an argument dynamically.
     *
     * @param array<string, mixed> $value
     */
    public function setSummary(string $key, array $value): void
    {
        $this->summary[$key] = $value;
    }

    public function getSummary(): mixed
    {
        return $this->summary ?? null;
    }
}
