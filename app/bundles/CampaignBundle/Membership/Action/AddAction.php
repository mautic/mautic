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

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Membership\Exception\ContactAlreadyInCampaignException;
use Mautic\CampaignBundle\Membership\Exception\ContactCannotBeAddedToCampaignException;
use Mautic\LeadBundle\Entity\Lead;

class AddAction
{
    const NAME = 'added';

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var LeadEventLogRepository
     */
    private $leadEventLogRepository;

    /**
     * AddAction constructor.
     *
     * @param LeadRepository         $leadRepository
     * @param LeadEventLogRepository $leadEventLogRepository
     */
    public function __construct(LeadRepository $leadRepository, LeadEventLogRepository $leadEventLogRepository)
    {
        $this->leadRepository         = $leadRepository;
        $this->leadEventLogRepository = $leadEventLogRepository;
    }

    /**
     * @param Lead     $contact
     * @param Campaign $campaign
     * @param          $isManualAction
     */
    public function createNewMembership(Lead $contact, Campaign $campaign, $isManualAction)
    {
        $campaignMember = new CampaignMember();
        $campaignMember->setLead($contact);
        $campaignMember->setCampaign($campaign);
        $campaignMember->setManuallyAdded($isManualAction);
        $campaignMember->setDateAdded(new \DateTime());

        // BC support for prior to 2.14.
        // If the contact was in the campaign to start with then removed, their logs remained but the original membership was removed
        // Start the new rotation at 2
        if ($this->leadEventLogRepository->hasBeenInCampaignRotation($contact->getId(), $campaign->getId(), 1)) {
            $campaignMember->setRotation(2);
        }

        $this->saveCampaignMember($campaignMember);
    }

    /**
     * @param CampaignMember $campaignMember
     * @param bool           $isManualAction
     *
     * @throws ContactAlreadyInCampaignException
     * @throws ContactCannotBeAddedToCampaignException
     */
    public function updateExistingMembership(CampaignMember $campaignMember, $isManualAction)
    {
        if (!$campaignMember->wasManuallyRemoved()) {
            // Contact is already in this campaign

            if ($campaignMember->wasManuallyAdded() !== $isManualAction) {
                // Update if this was now manually added or added by a segment
                $campaignMember->setManuallyAdded($isManualAction);

                $this->saveCampaignMember($campaignMember);
            }

            throw new ContactAlreadyInCampaignException();
        }

        if (!$isManualAction) {
            // This shouldn't happen because manually removed contact should not be automatically added back
            // but just in case, let's prevent it
            throw new ContactCannotBeAddedToCampaignException();
        }

        // Contact exited but has been added back to the campaign
        $campaignMember->setManuallyRemoved(false);
        $campaignMember->setManuallyAdded(true);
        $campaignMember->setDateLastExited(null);
        $campaignMember->startNewRotation();

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
