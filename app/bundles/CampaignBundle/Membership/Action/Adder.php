<?php

namespace Mautic\CampaignBundle\Membership\Action;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Membership\Exception\ContactCannotBeAddedToCampaignException;
use Mautic\LeadBundle\Entity\Lead;

class Adder
{
    public const NAME = 'added';

    public function __construct(
        private LeadRepository $leadRepository,
        private LeadEventLogRepository $leadEventLogRepository,
    ) {
    }

    public function createNewMembership(Lead $contact, Campaign $campaign, $isManualAction): CampaignMember
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
     * @param bool $isManualAction
     *
     * @throws ContactCannotBeAddedToCampaignException
     */
    public function updateExistingMembership(CampaignMember $campaignMember, $isManualAction): void
    {
        $wasRemoved = $campaignMember->wasManuallyRemoved();

        if (!$campaignMember->getCampaign()->allowRestart()) {
            // Only exception: manually removed contacts (no exit date) being manually re-added
            $isManualReAddOfManuallyRemoved = $wasRemoved && null === $campaignMember->getDateLastExited() && $isManualAction;

            if (!$isManualReAddOfManuallyRemoved) {
                // Block all other re-entry scenarios:
                // - Natural exits (wasRemoved=false, dateLastExited=set) being auto re-added
                // - Filter removals (wasRemoved=true, dateLastExited=set) being auto re-added
                // - Manual removals (wasRemoved=true, dateLastExited=null) being auto re-added
                // - Manual removals being manually re-added (unless caught above)
                throw new ContactCannotBeAddedToCampaignException('Contacts cannot restart the campaign');
            }
        }

        if ($wasRemoved && !$isManualAction && null === $campaignMember->getDateLastExited()) {
            // Prevent contacts from being added back if they were manually removed but automatically added back

            throw new ContactCannotBeAddedToCampaignException('Contact was manually removed');
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

    private function saveCampaignMember($campaignMember): void
    {
        $this->leadRepository->saveEntity($campaignMember);
        $this->leadRepository->detachEntity($campaignMember);
    }
}
