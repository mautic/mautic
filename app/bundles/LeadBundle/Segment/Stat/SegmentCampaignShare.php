<?php

namespace Mautic\LeadBundle\Segment\Stat;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CampaignBundle\Model\CampaignModel;

class SegmentCampaignShare
{
    public function __construct(
        private CampaignModel $campaignModel,
        private CacheProvider $cacheProvider,
        private EntityManager $entityManager,
    ) {
    }

    /**
     * @param int   $segmentId
     * @param array $campaignIds
     *
     * @return array
     */
    public function getCampaignsSegmentShare($segmentId, $campaignIds = [])
    {
        $campaigns = $this->campaignModel->getRepository()->getCampaignsSegmentShare($segmentId, $campaignIds);
        foreach ($campaigns as $campaign) {
            $cacheItem = $this->cacheProvider->getCacheAdapter()->getItem($this->getCachedKey($segmentId, $campaign['id']));
            $cacheItem->set($campaign['segmentCampaignShare']);
            $cacheItem->expiresAfter(3600);
            $this->cacheProvider->getCacheAdapter()->save($cacheItem);
        }

        return $campaigns;
    }

    /**
     * @throws Exception
     */
    public function getCampaignList(int $segmentId): array
    {
        $q = $this->entityManager->getConnection()->createQueryBuilder();
        $q->select('c.id, c.name')
            ->from(MAUTIC_TABLE_PREFIX.'campaigns', 'c')
            ->where($this->campaignModel->getRepository()->getPublishedByDateExpression($q))
            ->orderBy('c.id', 'DESC');

        $campaigns = $q->executeQuery()->fetchAllAssociative();

        foreach ($campaigns as $key=>$campaign) {
            $cacheKey = $this->getCachedKey($segmentId, $campaign['id']);

            // Only check if the item exists in cache
            if ($this->cacheProvider->hasItem($cacheKey)) {
                $campaigns[$key]['share'] = $this->cacheProvider->getItem($cacheKey)->get();
            }
        }

        usort($campaigns, function ($a, $b) { return floatval($b['share'] ?? 0) <=> floatval($a['share'] ?? 0); });

        return $campaigns;
    }

    /**
     * @param int $segmentId
     * @param int $campaignId
     */
    private function getCachedKey($segmentId, $campaignId): string
    {
        return sprintf('%s|%s|%s|%s|%s', 'campaign', $campaignId, 'segment', $segmentId, 'share');
    }
}
