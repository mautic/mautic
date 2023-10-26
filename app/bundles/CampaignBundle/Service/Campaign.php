<?php

namespace Mautic\CampaignBundle\Service;

use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\EmailBundle\Entity\EmailRepository;

class Campaign
{
    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * @var EmailRepository
     */
    private $emailRepository;

    public function __construct(CampaignRepository $campaignRepository, EmailRepository $emailRepository)
    {
        $this->campaignRepository = $campaignRepository;
        $this->emailRepository    = $emailRepository;
    }

    /**
     * Has campaign at least one unpublished e-mail?
     *
     * @param int $id
     *
     * @return bool
     */
    public function hasUnpublishedEmail($id)
    {
        $emailIds = $this->campaignRepository->fetchEmailIdsById($id);

        if (!$emailIds) {
            return false;
        }

        return $this->emailRepository->isOneUnpublished($emailIds);
    }
}
