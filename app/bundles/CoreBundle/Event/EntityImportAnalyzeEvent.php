<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EntityImportAnalyzeEvent extends Event
{
    /**
     * @var array<string, mixed>
     */
    private array $summary;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private array $data)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getEntityData(): array
    {
        return $this->data;
    }

    public function setSummary(mixed $value): mixed
    {
        return $this->summary = $value;
    }

    public function getSummary(): mixed
    {
        return $this->summary ?? null;
    }
}
