<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     * @param $id
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
