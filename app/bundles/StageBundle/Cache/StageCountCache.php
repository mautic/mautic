<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Cache;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Contracts\Cache\ItemInterface;

class StageCountCache
{
    private const EXPIRATION = 3600;

    public function __construct(private CacheProvider $cacheProvider, private StageModel $stageModel)
    {
    }

    public function getStageContactCount(int $stageId): int
    {
        return (int) $this->cacheProvider->getCacheAdapter()->get($this->generateCacheKey($stageId), function (ItemInterface $item) use ($stageId): int {
            $item->expiresAfter(self::EXPIRATION);

            return $this->stageModel->getRepository()->getContactCount($stageId);
        });
    }

    public function incrementStageContactCount(int $stageId): void
    {
        $item  = $this->cacheProvider->getCacheAdapter()->getItem($this->generateCacheKey($stageId));
        $count = $item->get() ?? ($this->getStageContactCount($stageId) - 1);
        if ($count > -1) {
            $item->set($count + 1);
            $this->cacheProvider->getCacheAdapter()->save($item);
        }
    }

    public function decrementStageContactCount(int $stageId): void
    {
        $item  = $this->cacheProvider->getCacheAdapter()->getItem($this->generateCacheKey($stageId));
        $count = $item->get() ?? ($this->getStageContactCount($stageId) + 1);
        if ($count > 0) {
            $value = $count - 1;
            $item->set($value);
            $this->cacheProvider->getCacheAdapter()->save($item);
        }
    }

    /**
     * @template T
     *
     * @param Paginator<T> $stages
     *
     * @return array<int>
     */
    public function getCountsFromCache(Paginator $stages): array
    {
        $counts = [];
        foreach ($stages as $stage) {
            $stageId          = $stage->getId();
            $items            = $this->cacheProvider->getCacheAdapter()->getItem($this->generateCacheKey($stageId));
            $counts[$stageId] = $items->get() ?? 0;
        }

        return $counts;
    }

    private function generateCacheKey(int $stageId): string
    {
        return sprintf('%s.%s.%s', 'stage', $stageId, 'lead');
    }
}
