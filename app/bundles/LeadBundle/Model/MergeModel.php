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

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\MergeRecord;
use Mautic\LeadBundle\Entity\MergeRecordRepository;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\LeadEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MergeModel
{
    /**
     * @var Lead
     */
    protected $winner;

    /**
     * @var Lead
     */
    protected $loser;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var MergeRecordRepository
     */
    protected $repo;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * MergeModel constructor.
     *
     * @param LeadModel             $leadModel
     * @param MergeRecordRepository $repo
     * @param LoggerInterface       $logger
     */
    public function __construct(LeadModel $leadModel, MergeRecordRepository $repo, EventDispatcherInterface $dispatcher, LoggerInterface $logger)
    {
        $this->leadModel  = $leadModel;
        $this->repo       = $repo;
        $this->logger     = $logger;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Lead $lead
     * @param Lead $lead2
     *
     * @return Lead
     */
    public function mergeOldIntoNew(Lead $lead, Lead $lead2)
    {
        $winner = ($lead->getDateAdded() > $lead2->getDateAdded()) ? $lead : $lead2;
        $loser  = ($winner->getId() === $lead->getId()) ? $lead2 : $lead;

        return $this->merge($loser, $winner);
    }

    /**
     * @param Lead $lead
     * @param Lead $lead2
     *
     * @return Lead
     */
    public function mergeNewIntoOld(Lead $lead, Lead $lead2)
    {
        $winner = ($lead->getDateAdded() < $lead2->getDateAdded()) ? $lead : $lead2;
        $loser  = ($winner->getId() === $lead->getId()) ? $lead2 : $lead;

        return $this->merge($loser, $winner);
    }

    /**
     * @param Lead $loser
     * @param Lead $winner
     *
     * @return Lead
     */
    public function merge(Lead $loser, Lead $winner)
    {
        $this->loser  = $loser;
        $this->winner = $winner;

        //if they are the same lead, then just return one
        if ($loser === $winner) {
            $this->logger->debug('CONTACT: Contacts are the same');

            return $loser;
        }

        $this->logger->debug('CONTACT: ID# '.$loser->getId().' will be merged into ID# '.$winner->getId());

        // Dispatch pre merge event
        $event = new LeadMergeEvent($winner, $loser);
        if ($this->dispatcher->hasListeners(LeadEvents::LEAD_PRE_MERGE)) {
            $this->dispatcher->dispatch(LeadEvents::LEAD_PRE_MERGE, $event);
        }

        // Merge everything
        $this->updateMergeRecords();
        $this->mergeTimestamps();
        $this->mergeIpAddressHistory();
        $this->mergeFieldData();
        $this->mergeOwners();
        $this->mergePoints();
        $this->mergeTags();

        // Save the updated contact
        $this->leadModel->saveEntity($winner, false);

        // Dispatch post merge event
        if ($this->dispatcher->hasListeners(LeadEvents::LEAD_POST_MERGE)) {
            $this->dispatcher->dispatch(LeadEvents::LEAD_POST_MERGE, $event);
        }

        // Delete the loser
        $this->leadModel->deleteEntity($loser);

        return $winner;
    }

    /**
     * Merge timestamps.
     */
    protected function mergeTimestamps()
    {
        // The winner should keep the most recent last active timestamp of the two
        if ($this->loser->getLastActive() > $this->winner->getLastActive()) {
            $this->winner->setLastActive($this->loser->getLastActive());
        }

        // The winner should keep the oldest date identified timestamp
        if ($this->loser->getDateIdentified() < $this->winner->getDateIdentified()) {
            $this->winner->setDateIdentified($this->loser->getDateIdentified());
        }
    }

    /**
     * Merge past merge records into the winner.
     */
    protected function updateMergeRecords()
    {
        // Update merge records for the lead about to be deleted
        $this->repo->moveMergeRecord($this->loser->getId(), $this->winner->getId());

        // Create an entry this contact was merged
        $mergeRecord = new MergeRecord();
        $mergeRecord->setContact($this->winner)
            ->setDateAdded()
            ->setName($this->loser->getPrimaryIdentifier())
            ->setMergedId($this->loser->getId());

        $this->repo->saveEntity($mergeRecord);
    }

    /**
     * Merge IP history into the winner.
     */
    protected function mergeIpAddressHistory()
    {
        $ipAddresses = $this->loser->getIpAddresses();
        foreach ($ipAddresses as $ip) {
            $this->winner->addIpAddress($ip);

            $this->logger->debug('CONTACT: Associating '.$this->winner->getId().' with IP '.$ip->getIpAddress());
        }
    }

    /**
     * Merge custom field data into winner.
     */
    protected function mergeFieldData()
    {
        // Use the modified date if applicable or date added if the contact has never been edited
        $loserDate  = ($this->loser->getDateModified()) ? $this->loser->getDateModified() : $this->loser->getDateAdded();
        $winnerDate = ($this->winner->getDateModified()) ? $this->winner->getDateModified() : $this->winner->getDateAdded();

        // When it comes to data, keep the newest value regardless of the winner/loser
        $newest = ($loserDate > $winnerDate) ? $this->loser : $this->winner;
        $oldest = ($newest->getId() === $this->winner->getId()) ? $this->loser : $this->winner;

        $newestFields = $newest->getProfileFields();
        $oldestFields = $oldest->getProfileFields();

        $winnerFields = $this->winner->getProfileFields();

        foreach (array_keys($winnerFields) as $field) {
            if ('points' === $field) {
                // Let mergePoints() take care of this
                continue;
            }

            // Don't overwrite with an empty value (error on the side of not losing any data)
            if ($this->valueIsEmpty($winnerFields[$field])) {
                // Give precedence to the newest value
                $newValue = (!$this->valueIsEmpty($newestFields[$field])) ? $newestFields[$field] : $oldestFields[$field];
            } elseif (!$this->valueIsEmpty($newestFields[$field]) && $winnerFields[$field] !== $newestFields[$field]) {
                $newValue = $newestFields[$field];
            }

            if (isset($newValue)) {
                $this->winner->addUpdatedField($field, $newValue);
                $fromValue = empty($winnerFields[$field]) ? 'empty' : $winnerFields[$field];
                $this->logger->debug("CONTACT: Updated $field from $fromValue to $newValue for {$this->winner->getId()}");
            }
        }
    }

    /**
     * Merge owners if the winner isn't already assigned an owner.
     */
    protected function mergeOwners()
    {
        $oldOwner = $this->winner->getOwner();
        $newOwner = $this->loser->getOwner();

        if ($oldOwner === null && $newOwner !== null) {
            $this->winner->setOwner($newOwner);

            $this->logger->debug("CONTACT: New owner of {$this->winner->getId()} is {$newOwner->getId()}");
        }
    }

    /**
     * Sum points from both contacts.
     */
    protected function mergePoints()
    {
        $winnerPoints = (int) $this->winner->getPoints();
        $loserPoints  = (int) $this->loser->getPoints();
        $this->winner->setPoints($winnerPoints + $loserPoints);
        $this->logger->debug("CONTACT: Adding {$loserPoints} points to {$this->winner->getId()}");
    }

    /**
     * Merge tags from loser into winner.
     */
    protected function mergeTags()
    {
        $loserTags = $this->loser->getTags();
        $addTags   = $loserTags->getKeys();

        $this->leadModel->modifyTags($this->winner, $addTags, null, false);
    }

    /**
     * Check if value is empty but don't include false or 0.
     *
     * @param $value
     *
     * @return bool
     */
    protected function valueIsEmpty($value)
    {
        return null === $value || '' === $value;
    }
}
