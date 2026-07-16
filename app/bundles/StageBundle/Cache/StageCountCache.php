<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Cache;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\StageBundle\Model\StageModel;

class StageCountCache
{
    private const EXPIRATION = 3600;

    public function __construct(
        private readonly CacheProviderInterface $cacheProvider,
        private readonly StageModel $stageModel,
    ) {
    }

    public function getStageContactCount(int $stageId): int
    {
        $item = $this->cacheProvider->getItem($this->generateCacheKey($stageId));

        if (!$item->isHit()) {
            $item->set($this->stageModel->getRepository()->getContactCount($stageId));
            $item->expiresAfter(self::EXPIRATION);
            $this->cacheProvider->save($item);
        }

        return (int) $item->get();
    }

    public function incrementStageContactCount(int $stageId): void
    {
        $item  = $this->cacheProvider->getItem($this->generateCacheKey($stageId));
        $count = $item->get() ?? ($this->getStageContactCount($stageId) - 1);
        if ($count > -1) {
            $item->set($count + 1);
            $this->cacheProvider->save($item);
        }
    }

    public function decrementStageContactCount(int $stageId): void
    {
        $item  = $this->cacheProvider->getItem($this->generateCacheKey($stageId));
        $count = $item->get() ?? ($this->getStageContactCount($stageId) + 1);
        if ($count > 0) {
            $value = $count - 1;
            $item->set($value);
            $this->cacheProvider->save($item);
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
            $items            = $this->cacheProvider->getItem($this->generateCacheKey($stageId));
            $counts[$stageId] = $items->get() ?? 0;
        }

        return $counts;
    }

    private function generateCacheKey(int $stageId): string
    {
        return sprintf('%s.%s.%s', 'stage', $stageId, 'lead');
    }
}
