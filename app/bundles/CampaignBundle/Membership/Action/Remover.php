<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Membership\Action;

use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Membership\Exception\ContactAlreadyRemovedFromCampaignException;

class Remover
{
    const NAME = 'removed';

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * Adder constructor.
     *
     * @param LeadRepository $leadRepository
     */
    public function __construct(LeadRepository $leadRepository)
    {
        $this->leadRepository = $leadRepository;
    }

    /**
     * @param CampaignMember $campaignMember
     * @param bool           $isExit
     *
     * @throws ContactAlreadyRemovedFromCampaignException
     */
    public function updateExistingMembership(CampaignMember $campaignMember, $isExit)
    {
        if ($isExit) {
            // Contact was removed by the change campaign action or a segment
            $campaignMember->setDateLastExited(new \DateTime());
        } else {
            $campaignMember->setDateLastExited(null);
        }

        if ($campaignMember->wasManuallyRemoved()) {
            $this->saveCampaignMember($campaignMember);

            // Contact was already removed from this campaign
            throw new ContactAlreadyRemovedFromCampaignException();
        }

        // Remove this contact from the campaign
        $campaignMember->setManuallyRemoved(true);
        $campaignMember->setManuallyAdded(false);

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
