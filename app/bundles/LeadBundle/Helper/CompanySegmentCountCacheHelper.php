<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Psr\Cache\InvalidArgumentException;

class CompanySegmentCountCacheHelper
{
    public function __construct(
        private CacheProviderInterface $cacheProvider,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getSegmentCompanyCount(int $segmentId): int
    {
        return (int) $this->cacheProvider->getItem($this->generateCacheKey($segmentId))->get();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setSegmentCompanyCount(int $segmentId, int $count): void
    {
        $item = $this->cacheProvider->getItem($this->generateCacheKey($segmentId));
        $item->set($count);

        $ttl = $this->coreParametersHelper->get('segment_api_count_cache_ttl', 43200);
        if ($ttl) {
            $item->expiresAfter($ttl);
        }

        $this->cacheProvider->save($item);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function hasSegmentCompanyCount(int $segmentId): bool
    {
        return $this->cacheProvider->hasItem($this->generateCacheKey($segmentId));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function invalidateSegmentCompanyCount(int $segmentId): void
    {
        if ($this->hasSegmentCompanyCount($segmentId)) {
            $this->cacheProvider->deleteItem($this->generateCacheKey($segmentId));
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function incrementSegmentCompanyCount(int $segmentId): void
    {
        $count = $this->hasSegmentCompanyCount($segmentId) ? $this->getSegmentCompanyCount($segmentId) : 0;
        $this->setSegmentCompanyCount($segmentId, ++$count);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function decrementSegmentCompanyCount(int $segmentId): void
    {
        if ($this->hasSegmentCompanyCount($segmentId)) {
            $count = $this->getSegmentCompanyCount($segmentId);

            if ($count <= 0) {
                $count = 1;
            }

            $this->setSegmentCompanyCount($segmentId, --$count);
        }
    }

    private function generateCacheKey(int $segmentId): string
    {
        return sprintf('%s.%s.%s', 'segment', $segmentId, 'company');
    }
}
