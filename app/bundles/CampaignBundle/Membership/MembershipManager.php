<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Membership;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Membership\Action\AddAction;
use Mautic\CampaignBundle\Membership\Action\RemoveAction;
use Mautic\CampaignBundle\Membership\Exception\ContactAlreadyInCampaignException;
use Mautic\CampaignBundle\Membership\Exception\ContactAlreadyRemovedFromCampaignException;
use Mautic\CampaignBundle\Membership\Exception\ContactCannotBeAddedToCampaignException;
use Mautic\LeadBundle\Entity\Lead;
use Monolog\Logger;

class MembershipManager
{
    const ACTION_ADDED   = 'added';
    const ACTION_REMOVED = 'removed';

    /**
     * @var AddAction
     */
    private $adder;

    /**
     * @var RemoveAction
     */
    private $remover;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * MembershipManager constructor.
     *
     * @param AddAction       $adder
     * @param RemoveAction    $remover
     * @param EventDispatcher $eventDispatcher
     * @param LeadRepository  $leadRepository
     * @param Logger          $logger
     */
    public function __construct(
        AddAction $adder,
        RemoveAction $remover,
        EventDispatcher $eventDispatcher,
        LeadRepository $leadRepository,
        Logger $logger
    ) {
        $this->adder           = $adder;
        $this->remover         = $remover;
        $this->eventDispatcher = $eventDispatcher;
        $this->leadRepository  = $leadRepository;
        $this->logger          = $logger;
    }

    /**
     * @param Lead     $contact
     * @param Campaign $campaign
     * @param bool     $isManualAction
     * @param bool     $allowRestart
     */
    public function addContact(Lead $contact, Campaign $campaign, $isManualAction = true, $allowRestart = false)
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
                $this->adder->updateExistingMembership($campaignMember, $isManualAction, $allowRestart);
                $this->logger->debug("CAMPAIGN: Membership for contact ID {$contact->getId()} in campaign ID {$campaign->getId()} was updated to be included.");

                // Notify listeners
                $this->eventDispatcher->dispatchMembershipChange($campaignMember->getLead(), $campaignMember->getCampaign(), AddAction::NAME);
            } catch (ContactAlreadyInCampaignException $exception) {
                // Do nothing
                $this->logger->debug("CAMPAIGN: Contact ID {$contact->getId()} is already in campaign ID {$campaign->getId()}.");
            } catch (ContactCannotBeAddedToCampaignException $exception) {
                // Do nothing
                $this->logger->debug("CAMPAIGN: Contact ID {$contact->getId()} could not be added campaign ID {$campaign->getId()} because they were manually removed.");
            }

            return;
        }

        // Contact is not already in the campaign so create a new entry
        $this->adder->createNewMembership($contact, $campaign, $isManualAction);

        $this->logger->debug("CAMPAIGN: Contact ID {$contact->getId()} was added to campaign ID {$campaign->getId()} as a new member.");

        // Notify listeners the contact has been added
        $this->eventDispatcher->dispatchMembershipChange($contact, $campaign, AddAction::NAME);
    }

    /**
     * @param array    $contacts
     * @param Campaign $campaign
     * @param bool     $isManualAction
     * @param bool     $allowRestart
     */
    public function addContacts(array $contacts, Campaign $campaign, $isManualAction = true, $allowRestart = false)
    {
        $keyById = $this->organizeContactsById($contacts);

        // Get a list of existing campaign members
        $campaignMembers = $this->leadRepository->getCampaignMembers(array_keys($keyById), $campaign);

        /** @var Lead $contact */
        foreach ($contacts as $contact) {
            if (isset($campaignMembers[$contact->getId()])) {
                try {
                    $this->adder->updateExistingMembership($campaignMembers[$contact->getId()], $isManualAction, $allowRestart);
                    $this->logger->debug("CAMPAIGN: Membership for contact ID {$contact->getId()} in campaign ID {$campaign->getId()} was updated to be included.");
                } catch (ContactAlreadyInCampaignException $exception) {
                    // Remove them from the keyById array so they are not included in the dispatched event
                    unset($keyById[$contact->getId()]);

                    $this->logger->debug("CAMPAIGN: Contact ID {$contact->getId()} is already in campaign ID {$campaign->getId()}.");
                } catch (ContactCannotBeAddedToCampaignException $exception) {
                    // Remove them from the keyById array so they are not included in the dispatched event
                    unset($keyById[$contact->getId()]);

                    $this->logger->debug("CAMPAIGN: Contact ID {$contact->getId()} could not be added campaign ID {$campaign->getId()} because they were manually removed.");
                }

                continue;
            }

            // Existing membership does not exist so create a new one
            $this->adder->createNewMembership($contact, $campaign, $isManualAction);

            $this->logger->debug("CAMPAIGN: Contact ID {$contact->getId()} was added to campaign ID {$campaign->getId()} as a new member.");
        }

        if (count($keyById)) {
            // Notifiy listeners
            $this->eventDispatcher->dispatchBatchMembershipChange($keyById, $campaign, AddAction::NAME);
        }

        // Clear entities from RAM
        $this->leadRepository->clear();
    }

    /**
     * @param Lead     $contact
     * @param Campaign $campaign
     * @param bool     $isExit
     */
    public function removeContact(Lead $contact, Campaign $campaign, $isExit = false)
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
            $this->eventDispatcher->dispatchMembershipChange($contact, $campaign, RemoveAction::NAME);
        } catch (ContactAlreadyRemovedFromCampaignException $exception) {
            // Do nothing

            $this->logger->debug("CAMPAIGN: Contact ID {$contact->getId()} was already removed from campaign ID {$campaign->getId()}.");
        }
    }

    /**
     * @param array    $contacts
     * @param Campaign $campaign
     * @param bool     $isExit
     */
    public function removeContacts(array $contacts, Campaign $campaign, $isExit = false)
    {
        $keyById = $this->organizeContactsById($contacts);

        // Get a list of existing campaign members
        $campaignMembers = $this->leadRepository->getCampaignMembers(array_keys($keyById), $campaign);

        /** @var Lead $contact */
        foreach ($contacts as $contact) {
            if (!isset($campaignMembers[$contact->getId()])) {
                // Contact is not in the campaign
                unset($keyById[$contact->getId()]);

                continue;
            }

            /** @var CampaignMember $campaignMember */
            $campaignMember = $campaignMembers[$contact->getId()];

            try {
                $this->remover->updateExistingMembership($campaignMember, $isExit);
                $this->logger->debug("CAMPAIGN: Contact ID {$contact->getId()} was removed from campaign ID {$campaign->getId()}.");

                $this->eventDispatcher->dispatchMembershipChange($contact, $campaign, RemoveAction::NAME);
            } catch (ContactAlreadyRemovedFromCampaignException $exception) {
                // Contact was already removed from this campaign
                unset($keyById[$contact->getId()]);

                $this->logger->debug("CAMPAIGN: Contact ID {$contact->getId()} was already removed from campaign ID {$campaign->getId()}.");
            }
        }

        if (count($keyById)) {
            // Notify listeners
            $this->eventDispatcher->dispatchBatchMembershipChange($keyById, $campaign, RemoveAction::NAME);
        }

        // Clear entities from RAM
        $this->leadRepository->clear();
    }

    /**
     * @param array $contacts
     *
     * @return array
     */
    private function organizeContactsById(array $contacts)
    {
        $keyById = [];

        /** @var Lead $contact */
        foreach ($contacts as $contact) {
            $keyById[$contact->getId()] = $contact;
        }

        return $keyById;
    }
}
