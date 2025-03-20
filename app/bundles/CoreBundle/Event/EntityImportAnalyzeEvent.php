<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EntityImportAnalyzeEvent extends Event
{
    private array $data;
    private array $summary;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

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
