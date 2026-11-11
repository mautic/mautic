<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after any entity's publish status is toggled, before it is saved.
 */
final class EntityTogglePublishEvent extends Event
{
    public function __construct(
        private readonly object $entity,
    ) {
    }

    public function getEntity(): object
    {
        return $this->entity;
    }
}
