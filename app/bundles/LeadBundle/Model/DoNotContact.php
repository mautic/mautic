<?php

namespace Mautic\LeadBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Mautic\CoreBundle\Model\MauticModelInterface;
use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\Lead;

class DoNotContact implements MauticModelInterface
{
    public function __construct(
        protected LeadModel $leadModel,
        protected DoNotContactRepository $dncRepo
    ) {
    }

    /**
     * Remove a Lead's DNC entry based on channel.
     *
     * @param int       $contactId
     * @param string    $channel
     * @param bool|true $persist
     * @param int|null  $reason
     */
    public function removeDncForContact($contactId, $channel, $persist = true, $reason = null): bool
    {
        $contact = $this->leadModel->getEntity($contactId);

        /** @var DNC $dnc */
        foreach ($contact->getDoNotContact() as $dnc) {
            if ($dnc->getChannel() === $channel) {
                // Skip if reason doesn't match
                // Some integrations (Sugar CRM) can use both reasons (unsubscribed, bounced)
                if ($reason && $dnc->getReason() != $reason) {
                    continue;
                }
                $contact->removeDoNotContactEntry($dnc);

                if ($persist) {
                    $this->leadModel->saveEntity($contact);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Create a DNC entry for a lead.
     *
     * @param \Mautic\LeadBundle\Entity\Lead|int|null $contactId
     * @param string|array                            $channel                  If an array with an ID, use the structure ['email' => 123]
     * @param string                                  $comments
     * @param int                                     $reason                   Must be a class constant from the DoNotContact class
     * @param bool                                    $persist
     * @param bool                                    $checkCurrentStatus
     * @param bool                                    $allowUnsubscribeOverride
     *
     * @return bool|DNC If a DNC entry is added or updated, returns the DoNotContact object. If a DNC is already present
     *                  and has the specified reason, nothing is done and this returns false
     */
    public function addDncForContact(
        $contactId,
        $channel,
        $reason = DNC::BOUNCED,
        $comments = '',
        $persist = true,
        $checkCurrentStatus = true,
        $allowUnsubscribeOverride = false
    ) {
        $dnc     = null;
        $contact = $this->leadModel->getEntity($contactId);

        if (null === $contact) {
            // Contact not found, nothing to do
            return false;
        }

        // if !$checkCurrentStatus, assume is contactable due to already being validated
        $isContactable = ($checkCurrentStatus) ? $this->isContactable($contact, $channel) : DNC::IS_CONTACTABLE;

        /** @var ArrayCollection<int, DNC> $dncEntities */
        $dncEntities = new ArrayCollection();
        // If they don't have a DNC entry yet
        if (DNC::IS_CONTACTABLE === $isContactable) {
            $dnc = $dncEntities[] = $this->createDncRecord($contact, $channel, $reason, $comments);
        } elseif ($isContactable !== $reason) {
            // Or if the given reason is different than the stated reason

            $dncEntities = $contact->getDoNotContact();
            foreach ($dncEntities as $dnc) {
                // Only update if the contact did not unsubscribe themselves or if the code forces it
                $allowOverride = ($allowUnsubscribeOverride || DNC::UNSUBSCRIBED !== $dnc->getReason());

                // Only update if the contact did not unsubscribe themselves
                if ($allowOverride && $dnc->getChannel() === $channel) {
                    // Note the outdated entry for listeners
                    $contact->removeDoNotContactEntry($dnc);

                    // Update the entry with the latest
                    $this->updateDncRecord($dnc, $contact, $channel, $reason, $comments);

                    break;
                }
            }
        }

        if (null !== $dnc && $persist) {
            // Use model saveEntity to trigger events for DNC change
            $this->leadModel->saveEntity($contact);
            $this->dncRepo->detachEntities($dncEntities->toArray());
            // need to force a collection to load items in the next call.
            $collection = $contact->getDoNotContact();
            if ($collection instanceof PersistentCollection) {
                $collection->setInitialized(false);
            }
        }

        return $dnc;
    }

    /**
     * @param string $channel
     *
     * @return int
     *
     * @see DNC This method can return boolean false, so be
     *                                             sure to always compare the return value against
     *                                             the class constants of DoNotContact
     */
    public function isContactable(Lead $contact, $channel)
    {
        if (is_array($channel)) {
            $channel = key($channel);
        }

        $dncEntries = $this->dncRepo->getEntriesByLeadAndChannel($contact, $channel);

        // If the lead has no entries in the DNC table, we're good to go
        if (empty($dncEntries)) {
            return DNC::IS_CONTACTABLE;
        }

        foreach ($dncEntries as $dnc) {
            if (DNC::IS_CONTACTABLE !== $dnc->getReason()) {
                return $dnc->getReason();
            }
        }

        return DNC::IS_CONTACTABLE;
    }

    public function createDncRecord(Lead $contact, $channel, $reason, $comments = null): DNC
    {
        $dnc = new DNC();

        if (is_array($channel)) {
            $channelId = reset($channel);
            $channel   = key($channel);

            $dnc->setChannelId((int) $channelId);
        }

        $dnc->setChannel($channel);
        $dnc->setReason($reason);
        $dnc->setLead($contact);
        $dnc->setDateAdded(new \DateTime());
        $dnc->setComments($comments);

        $contact->addDoNotContactEntry($dnc);

        return $dnc;
    }

    public function updateDncRecord(DNC $dnc, Lead $contact, $channel, $reason, $comments = null): void
    {
        // Update the DNC entry
        $dnc->setChannel($channel);
        $dnc->setReason($reason);
        $dnc->setLead($contact);
        $dnc->setDateAdded(new \DateTime());
        $dnc->setComments($comments);

        // Re-add the entry to the lead
        $contact->addDoNotContactEntry($dnc);
    }

    /**
     * @return DoNotContactRepository
     */
    public function getDncRepo()
    {
        return $this->dncRepo;
    }
}
