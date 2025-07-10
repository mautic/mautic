<?php

namespace Mautic\CampaignBundle\Membership;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Membership\Action\Adder;
use Mautic\CampaignBundle\Membership\Action\Remover;
use Mautic\CampaignBundle\Membership\Exception\ContactAlreadyRemovedFromCampaignException;
use Mautic\CampaignBundle\Membership\Exception\ContactCannotBeAddedToCampaignException;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class MembershipManager
{
    public const ACTION_ADDED   = 'added';

    public const ACTION_REMOVED = 'removed';

    private ?ProgressBar $progressBar = null;

    public function __construct(
        private Adder $adder,
        private Remover $remover,
        private EventDispatcher $eventDispatcher,
        private LeadRepository $leadRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param bool $isManualAction
     */
    public function addContact(Lead $contact, Campaign $campaign, $isManualAction = true): void
    {
        // Validate that contact is not already in the Campaign
        /** @var CampaignMember $campaignMember */
        $campaignMember = $this->leadRepository->findOneBy(
            [
                'lead'     => $contact,
                'campaign' => $campaign,
            ]
        );

        if ($campaignMember) {
            try {
                $this->adder->updateExistingMembership($campaignMember, $isManualAction);
                $this->logger->debug(
                    "CAMPAIGN: Membership for contact ID {$contact->getId()} in campaign ID {$campaign->getId()} was updated to be included."
                );

                // Notify listeners
                $this->eventDispatcher->dispatchMembershipChange($campaignMember->getLead(), $campaignMember->getCampaign(), Adder::NAME);
            } catch (ContactCannotBeAddedToCampaignException $exception) {
                // Do nothing
                $this->logger->debug(
                    "CAMPAIGN: Contact ID {$contact->getId()} could not be added to campaign ID {$campaign->getId()}.",
                    $exception->toArray()
                );
            }

            return;
        }

        try {
            // Contact is not already in the campaign so create a new entry
            $this->adder->createNewMembership($contact, $campaign, $isManualAction);
        } catch (ContactCannotBeAddedToCampaignException $exception) {
            // Do nothing
            $this->logger->debug(
                "CAMPAIGN: Contact ID {$contact->getId()} could not be added to campaign ID {$campaign->getId()}.",
                $exception->toArray()
            );

            return;
        }

        $this->logger->debug("CAMPAIGN: Contact ID {$contact->getId()} was added to campaign ID {$campaign->getId()} as a new member.");

        // Notify listeners the contact has been added
        $this->eventDispatcher->dispatchMembershipChange($contact, $campaign, Adder::NAME);
    }

    /**
     * @param ArrayCollection<int, Lead> $contacts
     * @param bool                       $isManualAction
     */
    public function addContacts(ArrayCollection $contacts, Campaign $campaign, $isManualAction = true): void
    {
        // Get a list of existing campaign members
        $campaignMembers = $this->leadRepository->getCampaignMembers($contacts->getKeys(), $campaign);

        foreach ($contacts as $contact) {
            $this->advanceProgressBar();

            $this->logger->debug(
                'CAMPAIGN: Contacts: '.count($contacts),
                array_map(fn ($item) => $item->getId(), $contacts->toArray())
            );

            // is the contact an existing campaign member? update and continue
            if (isset($campaignMembers[$contact->getId()])) {
                try {
                    $this->adder->updateExistingMembership($campaignMembers[$contact->getId()], $isManualAction);
                    $this->logger->debug(
                        "CAMPAIGN: Membership for contact ID {$contact->getId()} in campaign ID {$campaign->getId()} was updated to be included."
                    );
                } catch (ContactCannotBeAddedToCampaignException $exception) {
                    $contacts->remove($contact->getId());

                    $this->logger->debug(
                        "CAMPAIGN: Contact ID {$contact->getId()} could not be added to campaign ID {$campaign->getId()}.",
                        $exception->toArray()
                    );
                }

                continue;
            }

            // Existing membership does not exist so create a new one
            $this->adder->createNewMembership($contact, $campaign, $isManualAction);

            $this->logger->debug("CAMPAIGN: Contact ID {$contact->getId()} was added to campaign ID {$campaign->getId()} as a new member.");
        }

        if ($contacts->count()) {
            // Notifiy listeners
            $this->eventDispatcher->dispatchBatchMembershipChange($contacts->toArray(), $campaign, Adder::NAME);
        }

        // Clear entities from RAM
        $this->leadRepository->detachEntities($campaignMembers);
    }

    /**
     * @param bool $isExit
     */
    public function removeContact(Lead $contact, Campaign $campaign, $isExit = false): void
    {
        // Validate that contact is not already in the Campaign
        /** @var CampaignMember $campaignMember */
        $campaignMember = $this->leadRepository->findOneBy(
            [
                'lead'     => $contact,
                'campaign' => $campaign,
            ]
        );

        if (!$campaignMember) {
            // Contact is not in this campaign
            $this->logger->debug("CAMPAIGN: Contact ID {$contact->getId()} is not in campaign ID {$campaign->getId()}.");

            return;
        }

        try {
            $this->remover->updateExistingMembership($campaignMember, $isExit);
            $this->logger->debug("CAMPAIGN: Contact ID {$contact->getId()} was removed from campaign ID {$campaign->getId()}.");

            // Notify listeners
            $this->eventDispatcher->dispatchMembershipChange($contact, $campaign, Remover::NAME);
        } catch (ContactAlreadyRemovedFromCampaignException $exception) {
            // Do nothing

            $this->logger->debug(
                "CAMPAIGN: Contact ID {$contact->getId()} was already removed from campaign ID {$campaign->getId()}.",
                $exception->toArray()
            );
        }
    }

    /**
     * @param ArrayCollection<int, Lead> $contacts
     * @param bool                       $isExit   If true, the contact can be added by a segment/source. If false, the contact can only be added back
     *                                             by a manual process.
     */
    public function removeContacts(ArrayCollection $contacts, Campaign $campaign, $isExit = false): void
    {
        // Get a list of existing campaign members
        $campaignMembers = $this->leadRepository->getCampaignMembers($contacts->getKeys(), $campaign);

        foreach ($contacts as $contact) {
            $this->advanceProgressBar();

            if (!isset($campaignMembers[$contact->getId()])) {
                // Contact is not in the campaign
                $contacts->remove($contact->getId());

                continue;
            }

            /** @var CampaignMember $campaignMember */
            $campaignMember = $campaignMembers[$contact->getId()];

            try {
                $this->remover->updateExistingMembership($campaignMember, $isExit);
                $this->logger->debug("CAMPAIGN: Contact ID {$contact->getId()} was removed from campaign ID {$campaign->getId()}.");
            } catch (ContactAlreadyRemovedFromCampaignException $exception) {
                // Contact was already removed from this campaign
                $contacts->remove($contact->getId());

                $this->logger->debug(
                    "CAMPAIGN: Contact ID {$contact->getId()} was already removed from campaign ID {$campaign->getId()}.",
                    $exception->toArray()
                );
            }
        }

        if ($contacts->count()) {
            // Notify listeners
            $this->eventDispatcher->dispatchBatchMembershipChange($contacts->toArray(), $campaign, Remover::NAME);
        }

        // Clear entities from RAM
        $this->leadRepository->detachEntities($campaignMembers);
    }

    public function setProgressBar(ProgressBar $progressBar = null): void
    {
        $this->progressBar = $progressBar;
    }

    private function advanceProgressBar(): void
    {
        if ($this->progressBar) {
            $this->progressBar->advance();
        }
    }
}
