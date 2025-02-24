<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\CacheStorageHelper;

class SegmentCountCacheHelper
{
    public function __construct(
        private CacheStorageHelper $cacheStorageHelper
    ) {
    }

    /**
     * @throws \Exception
     */
    public function getSegmentContactCount(int $segmentId): int
    {
        return (int) $this->cacheStorageHelper->get($this->generateCacheKey($segmentId));
    }

    /**
     * @throws \Exception
     */
    public function setSegmentContactCount(int $segmentId, int $count): void
    {
        $this->cacheStorageHelper->set($this->generateCacheKey($segmentId), $count);
    }

    public function hasSegmentContactCount(int $segmentId): bool
    {
        return $this->cacheStorageHelper->has($this->generateCacheKey($segmentId));
    }

    public function invalidateSegmentContactCount(int $segmentId): void
    {
        if ($this->hasSegmentContactCount($segmentId)) {
            $this->cacheStorageHelper->delete($this->generateCacheKey($segmentId));
        }
    }

    /**
     * @throws \Exception
     */
    public function incrementSegmentContactCount(int $segmentId): void
    {
        $count = $this->hasSegmentContactCount($segmentId) ? $this->getSegmentContactCount($segmentId) : 0;
        $this->setSegmentContactCount($segmentId, ++$count);
    }

    /**
     * @throws \Exception
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
}
