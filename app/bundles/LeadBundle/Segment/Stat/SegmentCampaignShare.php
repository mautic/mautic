<?php

namespace Mautic\LeadBundle\Segment\Stat;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CampaignBundle\Model\CampaignModel;
use Symfony\Contracts\Cache\ItemInterface;

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
            $this->cacheProvider->getSimpleCache()->set($this->getCachedKey($segmentId, $campaign['id']), $campaign['segmentCampaignShare']);
        }

        return $campaigns;
    }

    /**
     * @throws Exception
     */
    public function getCampaignList(int $segmentId): array
    {
        $q = $this->entityManager->getConnection()->createQueryBuilder();
        $q->select('c.id, c.name, null as share')
            ->from(MAUTIC_TABLE_PREFIX.'campaigns', 'c')
            ->where($this->campaignModel->getRepository()->getPublishedByDateExpression($q))
            ->orderBy('c.id', 'DESC');

        $campaigns = $q->executeQuery()->fetchAllAssociative();

        foreach ($campaigns as $key=>$campaign) {
            $cacheKey = $this->getCachedKey($segmentId, $campaign['id']);
            
            // Only check if the item exists in cache
            if ($this->cacheProvider->getCacheAdapter()->hasItem($cacheKey)) {
                // Get the value from cache if it exists
                $item = $this->cacheProvider->getCacheAdapter()->getItem($cacheKey);
                $campaigns[$key]['share'] = $item->get();
            } else {
                // Use default value if not in cache
                $campaigns[$key]['share'] = '0';
            }
        }

        usort($campaigns, function ($a, $b) { return floatval($b['share']) <=> floatval($a['share']); });

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
