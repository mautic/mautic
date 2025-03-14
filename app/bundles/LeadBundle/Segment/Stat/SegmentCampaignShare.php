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

        // rewrite this only to get from cache if exist. Do not make call getCampaignsSegmentShare AI!
        foreach ($campaigns as $key=>$campaign) {
            $campaigns[$key]['share'] = $this->cacheProvider->getCacheAdapter()->get(
                $this->getCachedKey($segmentId, $campaign['id']),
                function (ItemInterface $item) use ($segmentId, $campaign): string {
                    $item->expiresAfter(new \DateInterval('PT1H'));

                    return $this->campaignModel->getRepository()->getCampaignsSegmentShare($segmentId, [$campaign['id']])[0]['segmentCampaignShare'];
                }
            );
        }

        usort($campaigns, function ($a, $b) { return floatval($b['share']) <=> floatval($a['share']); });*/

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
