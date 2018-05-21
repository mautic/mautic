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
use Mautic\CampaignBundle\Membership\Exception\ContactAlreadyInCampaignException;
use Mautic\CampaignBundle\Membership\Exception\ContactAlreadyRemovedFromCampaignException;
use Mautic\CampaignBundle\Membership\Exception\ContactCannotBeAddedToCampaignException;
use Mautic\CoreBundle\Membership\Action\AddAction;
use Mautic\CoreBundle\Membership\Action\RemoveAction;
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
     * @param bool     $manuallyAdded
     */
    public function addContact(Lead $contact, Campaign $campaign, $manuallyAdded = true)
    {
        // Validate that contact is not already in the Campaign
        /** @var CampaignMember $campaignMember */
        $campaignMember = $this->leadRepository()->findOneBy(
            [
                'lead'     => $contact,
                'campaign' => $campaign,
            ]
        );

        if ($campaignMember) {
            try {
                $this->adder->updateExistingMembership($campaignMember, $manuallyAdded);

                // Notify listeners
                $this->eventDispatcher->dispatchMembershipChange($campaignMember->getLead(), $campaignMember->getCampaign(), AddAction::NAME);
            } catch (ContactAlreadyInCampaignException $exception) {
                // Do nothing
            } catch (ContactCannotBeAddedToCampaignException $exception) {
                // Do nothing
            }

            return;
        }

        // Contact is not already in the campaign so create a new entry
        $this->adder->createNewMembership($contact, $campaign, $manuallyAdded);

        // Notify listeners the contact has been added
        $this->eventDispatcher->dispatchMembershipChange($contact, $campaign, AddAction::NAME);
    }

    /**
     * @param Lead[]   $contacts
     * @param Campaign $campaign
     * @param bool     $manuallyAdded
     */
    public function addContacts(array $contacts, Campaign $campaign, $manuallyAdded = true)
    {
        $keyById = $this->organizeContactsById($contacts);

        // Get a list of existing campaign members
        $campaignMembers = $this->leadRepository->getCampaignMembers(array_keys($keyById), $campaign);

        /** @var Lead $contact */
        foreach ($contacts as $contact) {
            if (isset($campaignMembers[$contact->getId()])) {
                try {
                    $this->adder->updateExistingMembership($campaignMembers[$contact->getId()], $manuallyAdded);
                } catch (ContactAlreadyInCampaignException $exception) {
                    // Remove them from the keyById array so they are not included in the dispatched event
                    unset($keyById[$contact->getId()]);
                }

                continue;
            }

            // Existing membership does not exist so create a new one
            $this->adder->execute($contact, $campaign, $manuallyAdded);
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
     * @param bool     $manuallyRemoved
     */
    public function removeContact(Lead $contact, Campaign $campaign, $manuallyRemoved = true)
    {
        // Validate that contact is not already in the Campaign
        /** @var CampaignMember $campaignMember */
        $campaignMember = $this->leadRepository()->findOneBy(
            [
                'lead'     => $contact,
                'campaign' => $campaign,
            ]
        );

        if (!$campaignMember) {
            // Contact is not in this campaign
            return;
        }

        try {
            $this->remover->updateExistingMembership($campaignMember, $manuallyRemoved);

            // Notify listeners
            $this->eventDispatcher->dispatchMembershipChange($contact, $campaign, RemoveAction::NAME);
        } catch (ContactAlreadyRemovedFromCampaignException $exception) {
            // Do nothing
        }
    }

    /**
     * @param array    $contacts
     * @param Campaign $campaign
     * @param bool     $manuallyRemoved
     */
    public function removeContacts(array $contacts, Campaign $campaign, $manuallyRemoved = true)
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
                $this->remover->updateExistingMembership($campaignMember, $manuallyRemoved);

                $this->eventDispatcher->dispatchMembershipChange($contact, $campaign, RemoveAction::NAME);
            } catch (ContactAlreadyRemovedFromCampaignException $exception) {
                // Contact was already removed from this campaign
                unset($keyById[$contact->getId()]);
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
