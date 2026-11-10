<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched right before any entity's publish status is toggled and saved.
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
