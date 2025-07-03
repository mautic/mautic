<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Psr\Cache\InvalidArgumentException;

class SegmentCountCacheHelper
{
    public function __construct(
        private CacheStorageHelper $cacheStorageHelper,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getSegmentContactCount(int $segmentId): int
    {
        return (int) $this->cacheStorageHelper->get($this->generateCacheKey($segmentId));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setSegmentContactCount(int $segmentId, int $count): void
    {
        $this->cacheStorageHelper->set($this->generateCacheKey($segmentId), $count);
        if ($this->hasSegmentIdForReCount($segmentId)) {
            $this->cacheStorageHelper->delete($this->generateCacheKeyForRecount($segmentId));
        }
    }

    public function hasSegmentContactCount(int $segmentId): bool
    {
        return $this->cacheStorageHelper->has($this->generateCacheKey($segmentId));
    }

    public function hasSegmentIdForReCount(int $segmentId): bool
    {
        return $this->cacheStorageHelper->has($this->generateCacheKeyForRecount($segmentId));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function invalidateSegmentContactCount(int $segmentId): void
    {
        $this->cacheStorageHelper->set($this->generateCacheKeyForRecount($segmentId), true);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function incrementSegmentContactCount(int $segmentId): void
    {
        $count = $this->hasSegmentContactCount($segmentId) ? $this->getSegmentContactCount($segmentId) : 0;
        $this->setSegmentContactCount($segmentId, ++$count);
    }

    public function deleteSegmentContactCount(int $segmentId): void
    {
        if ($this->hasSegmentContactCount($segmentId)) {
            $this->cacheStorageHelper->delete($this->generateCacheKey($segmentId));
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function decrementSegmentContactCount(int $segmentId): void
    {
        if ($this->hasSegmentContactCount($segmentId)) {
            $count = $this->getSegmentContactCount($segmentId);

            if ($count <= 0) {
                $count = 1;
            }

            $this->setSegmentContactCount($segmentId, --$count);
        }
    }

    private function generateCacheKey(int $segmentId): string
    {
        return sprintf('%s.%s.%s', 'segment', $segmentId, 'lead');
    }

    private function generateCacheKeyForRecount(int $segmentId): string
    {
        return sprintf('%s.%s.%s.%s', 'segment', $segmentId, 'lead', 'recount');
    }
}
