<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EntityImportUndoEvent extends Event
{
    /**
     * @param array<string, mixed> $summary
     */
    public function __construct(private array $summary)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return $this->summary;
    }
}
