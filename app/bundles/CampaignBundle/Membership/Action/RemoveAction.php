<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Membership\Action;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Membership\Exception\ContactAlreadyRemovedFromCampaignException;

class RemoveAction
{
    const NAME = 'removed';

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * AddAction constructor.
     *
     * @param LeadRepository $leadRepository
     */
    public function __construct(LeadRepository $leadRepository)
    {
        $this->leadRepository = $leadRepository;
    }

    /**
     * @param CampaignMember $campaignMember
     * @param bool           $isManualAction
     *
     * @throws ContactAlreadyRemovedFromCampaignException
     */
    public function updateExistingMembership(CampaignMember $campaignMember, $isManualAction)
    {
        if ($campaignMember->wasManuallyRemoved()) {
            // Contact was already removed from this campaign
            throw new ContactAlreadyRemovedFromCampaignException();
        }

        // Remove this contact from the campaign
        $campaignMember->setManuallyRemoved(true);

        if ($isManualAction) {
            // Forced out
            $campaignMember->setDateLastExited(new \DateTime());
        } else {
            // Filtered out
            $campaignMember->setDateLastExited(null);
        }

        $this->saveCampaignMember($campaignMember);
    }

    /**
     * @param $campaignMember
     */
    private function saveCampaignMember($campaignMember)
    {
        $this->leadRepository->saveEntity($campaignMember);
        $this->leadRepository->detachEntity($campaignMember);
    }
}
