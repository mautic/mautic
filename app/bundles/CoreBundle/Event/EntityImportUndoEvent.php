<?php

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EntityImportUndoEvent extends Event
{
    private array $summary;

    public function __construct(array $summary)
    {
        $this->summary = $summary;
    }

    public function getSummary(): array
    {
        return $this->summary;
    }
}
