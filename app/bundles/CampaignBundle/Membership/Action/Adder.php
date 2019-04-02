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
use Mautic\CampaignBundle\Membership\Exception\ContactCannotBeAddedToCampaignException;
use Mautic\LeadBundle\Entity\Lead;

class Adder
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
     * Adder constructor.
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
     *
     * @return CampaignMember
     */
    public function createNewMembership(Lead $contact, Campaign $campaign, $isManualAction)
    {
        // BC support for prior to 2.14.
        // If the contact was in the campaign to start with then removed, their logs remained but the original membership was removed
        // Start the new rotation at 2
        $rotation = 1;
        if ($this->leadEventLogRepository->hasBeenInCampaignRotation($contact->getId(), $campaign->getId(), 1)) {
            $rotation = 2;
        }

        $campaignMember = new CampaignMember();
        $campaignMember->setLead($contact);
        $campaignMember->setCampaign($campaign);
        $campaignMember->setManuallyAdded($isManualAction);
        $campaignMember->setDateAdded(new \DateTime());
        $campaignMember->setRotation($rotation);
        $this->saveCampaignMember($campaignMember);

        return $campaignMember;
    }

    /**
     * @param CampaignMember $campaignMember
     * @param bool           $isManualAction
     *
     * @throws ContactCannotBeAddedToCampaignException
     */
    public function updateExistingMembership(CampaignMember $campaignMember, $isManualAction)
    {
        $wasRemoved = $campaignMember->wasManuallyRemoved();
        if (!($wasRemoved && $isManualAction) && !$campaignMember->getCampaign()->allowRestart()) {
            // A contact cannot restart this campaign

            throw new ContactCannotBeAddedToCampaignException();
        }

        if ($wasRemoved && !$isManualAction && null === $campaignMember->getDateLastExited()) {
            // Prevent contacts from being added back if they were manually removed but automatically added back

            throw new ContactCannotBeAddedToCampaignException();
        }

        if ($wasRemoved && $isManualAction) {
            // If they were manually removed and manually added back, mark it as so
            $campaignMember->setManuallyAdded($isManualAction);
        }

        // Contact exited but has been added back to the campaign
        $campaignMember->setManuallyRemoved(false);
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
