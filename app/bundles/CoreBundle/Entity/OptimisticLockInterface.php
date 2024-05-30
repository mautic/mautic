<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

/**
 * Optimistic locking is applied for entities that implements this interface.
 */
interface OptimisticLockInterface
{
    /**
     * Returns the current version of the entity.
     */
    public function getVersion(): int;

    /**
     * Sets a new version of the entity and resets the mark for incrementing the version.
     */
    public function setVersion(int $version): void;

    /**
     * Returns true if the entity is marked for incrementing the version in a subsequent flush call.
     */
    public function isMarkedForVersionIncrement(): bool;

    /**
     * Mark the entity for incrementing the version in a subsequent flush call.
     */
    public function markForVersionIncrement(): void;

    /**
     * Returns the name of the version field.
     */
    public function getVersionField(): string;
}
