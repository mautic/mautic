<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Entity\DoNotContactRepository;
use Mautic\LeadBundle\Entity\Lead;

class DoNotContact
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var DoNotContactRepository
     */
    protected $dncRepo;

    /**
     * DoNotContact constructor.
     *
     * @param LeadModel              $leadModel
     * @param DoNotContactRepository $dncRepo
     */
    public function __construct(LeadModel $leadModel, DoNotContactRepository $dncRepo)
    {
        $this->leadModel = $leadModel;
        $this->dncRepo   = $dncRepo;
    }

    /**
     * Remove a Lead's DNC entry based on channel.
     *
     * @param int       $contactId
     * @param string    $channel
     * @param bool|true $persist
     *
     * @return bool
     */
    public function removeDncForContact($contactId, $channel, $persist = true)
    {
        $contact = $this->leadModel->getEntity($contactId);

        /** @var DNC $dnc */
        foreach ($contact->getDoNotContact() as $dnc) {
            if ($dnc->getChannel() === $channel) {
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
     * @param int          $contactId
     * @param string|array $channel                  If an array with an ID, use the structure ['email' => 123]
     * @param string       $comments
     * @param int          $reason                   Must be a class constant from the DoNotContact class
     * @param bool         $persist
     * @param bool         $checkCurrentStatus
     * @param bool         $allowUnsubscribeOverride
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
        $dnc     = false;
        $contact = $this->leadModel->getEntity($contactId);

        // if !$checkCurrentStatus, assume is contactable due to already being valided
        $isContactable = ($checkCurrentStatus) ? $this->isContactable($contact, $channel) : DNC::IS_CONTACTABLE;

        // If they don't have a DNC entry yet
        if ($isContactable === DNC::IS_CONTACTABLE) {
            $dnc = $this->createDncRecord($contact, $channel, $reason, $comments);
        } elseif ($isContactable !== $reason) {
            // Or if the given reason is different than the stated reason

            /** @var DNC $dnc */
            foreach ($contact->getDoNotContact() as $dnc) {
                // Only update if the contact did not unsubscribe themselves or if the code forces it
                $allowOverride = ($allowUnsubscribeOverride || $dnc->getReason() !== DNC::UNSUBSCRIBED);

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

        if ($dnc && $persist) {
            // Use model saveEntity to trigger events for DNC change
            $this->leadModel->saveEntity($contact);
        }

        return $dnc;
    }

    /**
     * @param Lead   $contact
     * @param string $channel
     *
     * @return int
     *
     * @see \Mautic\LeadBundle\Entity\DoNotContact This method can return boolean false, so be
     *                                             sure to always compare the return value against
     *                                             the class constants of DoNotContact
     */
    public function isContactable(Lead $contact, $channel)
    {
        if (is_array($channel)) {
            $channel = key($channel);
        }

        /** @var \Mautic\LeadBundle\Entity\DoNotContact[] $entries */
        $dncEntries = $this->dncRepo->getEntriesByLeadAndChannel($contact, $channel);

        // If the lead has no entries in the DNC table, we're good to go
        if (empty($dncEntries)) {
            return DNC::IS_CONTACTABLE;
        }

        foreach ($dncEntries as $dnc) {
            if ($dnc->getReason() !== DNC::IS_CONTACTABLE) {
                return $dnc->getReason();
            }
        }

        return DNC::IS_CONTACTABLE;
    }

    /**
     * @param      $channel
     * @param      $reason
     * @param Lead $contact
     * @param null $comments
     *
     * @return DNC
     */
    public function createDncRecord(Lead $contact, $channel, $reason, $comments = null)
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

    /**
     * @param DNC  $dnc
     * @param Lead $contact
     * @param      $channel
     * @param      $reason
     * @param null $comments
     */
    public function updateDncRecord(DNC $dnc, Lead $contact, $channel, $reason, $comments = null)
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
     * Clear DoNotContact entities from Doctrine UnitOfWork.
     */
    public function clearEntities()
    {
        $this->dncRepo->clear();
    }
}
