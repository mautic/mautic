<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Psr\Cache\InvalidArgumentException;

class SegmentCountCacheHelper
{
    /**
     * @var CacheStorageHelper
     */
    private $cacheStorageHelper;

    public function __construct(CacheStorageHelper $cacheStorageHelper)
    {
        $this->cacheStorageHelper = $cacheStorageHelper;
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
    public function decrementSegmentContactCount(int $segmentId): void
    {
        if ($this->hasSegmentContactCount($segmentId)) {
            $count = $this->getSegmentContactCount($segmentId);
            $this->setSegmentContactCount($segmentId, --$count);
        }
    }

    private function generateCacheKey(int $segmentId): string
    {
        return sprintf('%s.%s.%s', 'segment', $segmentId, 'lead');
    }
}
