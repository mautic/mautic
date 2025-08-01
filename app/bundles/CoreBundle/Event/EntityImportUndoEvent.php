<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class EntityImportUndoEvent extends Event
{
    /**
     * @param array<string, mixed> $summary
     */
    public function __construct(private string $entityName, private array $summary)
    {
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return $this->summary;
    }
}
