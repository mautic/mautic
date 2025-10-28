<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class EntityImportAnalyzeEvent extends Event
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
     * @param array<string, mixed> $value
     */
    public function setSummary(string $key, array $value): void
    {
        $this->summary[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummary(): ?array
    {
        return $this->summary ?? null;
    }
}
