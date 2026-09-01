<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Model;

interface ApiLockAwareInterface
{
    /**
     * Determines if the given entity is locked and should not be edited via API.
     */
    public function isApiLocked(object $entity): bool;
}
