<?php

namespace Mautic\CampaignBundle\Service;

use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\EmailBundle\Entity\EmailRepository;

/**
 * @deprecated to be removed in 6.0; use CampaignAuditService instead
 */
class Campaign
{
    public function __construct(
        private CampaignRepository $campaignRepository,
        private EmailRepository $emailRepository,
    ) {
    }

    /**
     * Has campaign at least one unpublished e-mail?
     *
     * @param int $id
     */
    public function hasUnpublishedEmail($id): bool
    {
        $emailIds = $this->campaignRepository->fetchEmailIdsById($id);

        if (!$emailIds) {
            return false;
        }

        return $this->emailRepository->isOneUnpublished($emailIds);
    }
}
