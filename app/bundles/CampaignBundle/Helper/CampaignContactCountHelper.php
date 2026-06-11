<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Helper;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;

class CampaignContactCountHelper
{
    private const CACHE_TTL = 43200;

    public function __construct(
        private CacheProviderInterface $cacheProvider,
        private CoreParametersHelper $coreParametersHelper,
        private LeadRepository $campaignLeadRepository,
    ) {
    }

    /**
     * @param array<int> $campaignIds
     *
     * @return array<mixed>
     */
    public function getContactCounts(array $campaignIds): array
    {
        $contactCounts = $campaignIdsForCountFromDb = [];
        foreach ($campaignIds as $campaignId) {
            $cacheKey = $this->generateCacheKey($campaignId);
            if ($this->cacheProvider->hasItem($cacheKey)) {
                $contactCounts[$campaignId] = $this->cacheProvider->getItem($cacheKey)->get();
            } else {
                $campaignIdsForCountFromDb[] = $campaignId;
            }
        }

        if (empty($campaignIdsForCountFromDb)) {
            return $contactCounts;
        }

        $campaignContactCounts = $this->campaignLeadRepository->getCampaignContactCounts($campaignIdsForCountFromDb);

        foreach ($campaignContactCounts as $count) {
            $campaignId = (int) $count['campaign_id'];

            $contactCountDetail = [
                'contactCount'   => (int) $count['contact_count'],
                'countFetchedAt' => (new DateTimeHelper())->getUtcDateTime()->format(DATE_ATOM),
            ];
            $contactCounts[$campaignId] = $contactCountDetail;
            $this->setContactCountInCache($campaignId, $contactCountDetail);
        }

        return $contactCounts;
    }

    /**
     * @param mixed[] $contactCountDetail
     */
    private function setContactCountInCache(int $campaignId, array $contactCountDetail): void
    {
        $item = $this->cacheProvider->getItem($this->generateCacheKey($campaignId));
        $item->set($contactCountDetail);
        $item->expiresAfter(
            $this->coreParametersHelper->get('campaign_contact_count_cache_ttl', self::CACHE_TTL)
        );
        $this->cacheProvider->save($item);
    }

    private function generateCacheKey(int $campaignId): string
    {
        return sprintf('%s.%s.%s', 'campaign', $campaignId, 'lead');
    }
}
