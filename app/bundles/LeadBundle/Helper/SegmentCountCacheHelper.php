<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Psr\Cache\InvalidArgumentException;

class SegmentCountCacheHelper
{
    public function __construct(
        private CacheProviderInterface $cacheProvider,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getSegmentContactCount(int $segmentId): int
    {
        return (int) $this->cacheProvider->getItem($this->generateCacheKey($segmentId))->get();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setSegmentContactCount(int $segmentId, int $count): void
    {
        $item = $this->cacheProvider->getItem($this->generateCacheKey($segmentId));
        $item->set($count);
        $this->cacheProvider->save($item);

        if ($this->hasSegmentIdForReCount($segmentId)) {
            $this->cacheProvider->deleteItem($this->generateCacheKeyForRecount($segmentId));
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function hasSegmentContactCount(int $segmentId): bool
    {
        return $this->cacheProvider->hasItem($this->generateCacheKey($segmentId));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function hasSegmentIdForReCount(int $segmentId): bool
    {
        return $this->cacheProvider->hasItem($this->generateCacheKeyForRecount($segmentId));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function invalidateSegmentContactCount(int $segmentId): void
    {
        $item = $this->cacheProvider->getItem($this->generateCacheKeyForRecount($segmentId));
        $item->set(true);
        $this->cacheProvider->save($item);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function incrementSegmentContactCount(int $segmentId): void
    {
        $count = $this->hasSegmentContactCount($segmentId) ? $this->getSegmentContactCount($segmentId) : 0;
        $this->setSegmentContactCount($segmentId, ++$count);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function deleteSegmentContactCount(int $segmentId): void
    {
        if ($this->hasSegmentContactCount($segmentId)) {
            $this->cacheProvider->deleteItem($this->generateCacheKey($segmentId));
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
        return sprintf('%s.%s', $this->generateCacheKey($segmentId), 'recount');
    }
}
