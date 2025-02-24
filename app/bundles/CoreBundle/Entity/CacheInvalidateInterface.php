<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

/**
 * An automatic cache invalidation is performed for entities that implements this interface.
 */
interface CacheInvalidateInterface
{
    /**
     * Returns the list of cache namespaces to delete.
     *
     * @return string[]
     */
    public function getCacheNamespacesToDelete(): array;
}
