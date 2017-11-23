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
use Mautic\LeadBundle\Exception\MissingMergeSubjectException;
use Mautic\LeadBundle\Exception\SameContactException;
use Mautic\LeadBundle\Exception\ValueNotMergeable;
use Mautic\LeadBundle\Helper\MergeValueHelper;
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
     * @param Lead $winner
     *
     * @return MergeModel
     */
    public function setWinner(Lead $winner)
    {
        $this->winner = $winner;

        return $this;
    }

    /**
     * @param Lead $loser
     *
     * @return MergeModel
     */
    public function setLoser(Lead $loser)
    {
        $this->loser = $loser;

        return $this;
    }

    /**
     * @param Lead $loser
     * @param Lead $winner
     *
     * @return Lead
     * @throws MissingMergeSubjectException
     */
    public function merge()
    {
        try {
            $this->checkIfMergeable();
        } catch (SameContactException $exception) {
            $this->logger->debug('CONTACT: Contacts are the same');

            return $this->winner;
        }

        $this->logger->debug('CONTACT: ID# '.$this->loser->getId().' will be merged into ID# '.$this->winner->getId());

        // Dispatch pre merge event
        $event = new LeadMergeEvent($this->winner, $this->loser);
        $this->dispatcher->dispatch(LeadEvents::LEAD_PRE_MERGE, $event);

        // Merge everything
        try {
            $this->updateMergeRecords()
                ->mergeTimestamps()
                ->mergeIpAddressHistory()
                ->mergeFieldData()
                ->mergeOwners()
                ->mergePoints()
                ->mergeTags();
        } catch (SameContactException $exception) {
            // Already handled; this is to just to make IDE happy
        } catch (MissingMergeSubjectException $exception) {
            // Already handled; this is to just to make IDE happy
        }

        // Save the updated contact
        $this->leadModel->saveEntity($this->winner, false);

        // Dispatch post merge event
        $this->dispatcher->dispatch(LeadEvents::LEAD_POST_MERGE, $event);

        // Delete the loser
        $this->leadModel->deleteEntity($this->loser);

        return $this->winner;
    }

    /**
     * Merge timestamps.
     *
     * @return $this
     * @throws SameContactException
     * @throws MissingMergeSubjectException
     */
    public function mergeTimestamps()
    {
        $this->checkIfMergeable();

        // The winner should keep the most recent last active timestamp of the two
        if ($this->loser->getLastActive() > $this->winner->getLastActive()) {
            $this->winner->setLastActive($this->loser->getLastActive());
        }

        // The winner should keep the oldest date identified timestamp
        if ($this->loser->getDateIdentified() < $this->winner->getDateIdentified()) {
            $this->winner->setDateIdentified($this->loser->getDateIdentified());
        }

        return $this;
    }

    /**
     * Merge IP history into the winner.
     *
     * @return $this
     * @throws SameContactException
     * @throws MissingMergeSubjectException
     */
    public function mergeIpAddressHistory()
    {
        $this->checkIfMergeable();

        $ipAddresses = $this->loser->getIpAddresses();
        foreach ($ipAddresses as $ip) {
            $this->winner->addIpAddress($ip);

            $this->logger->debug('CONTACT: Associating '.$this->winner->getId().' with IP '.$ip->getIpAddress());
        }

        return $this;
    }

    /**
     * Merge custom field data into winner.
     *
     * @return $this
     * @throws SameContactException
     * @throws MissingMergeSubjectException
     */
    public function mergeFieldData()
    {
        $this->checkIfMergeable();

        // Use the modified date if applicable or date added if the contact has never been edited
        $loserDate  = ($this->loser->getDateModified()) ? $this->loser->getDateModified() : $this->loser->getDateAdded();
        $winnerDate = ($this->winner->getDateModified()) ? $this->winner->getDateModified() : $this->winner->getDateAdded();

        // When it comes to data, keep the newest value regardless of the winner/loser
        $newest = ($loserDate > $winnerDate) ? $this->loser : $this->winner;
        $oldest = ($newest->getId() === $this->winner->getId()) ? $this->loser : $this->winner;

        $newestFields = $newest->getProfileFields();
        $oldestFields = $oldest->getProfileFields();

        foreach (array_keys($newestFields) as $field) {
            if (in_array($field, ['id', 'points'])) {
                // Let mergePoints() take care of this
                continue;
            }

            try {
                $newValue = MergeValueHelper::getMergeValue($newestFields[$field], $oldestFields[$field]);
                $this->winner->addUpdatedField($field, $newValue);

                $fromValue = empty($winnerFields[$field]) ? 'empty' : $winnerFields[$field];
                $this->logger->debug("CONTACT: Updated $field from $fromValue to $newValue for {$this->winner->getId()}");
            } catch (ValueNotMergeable $exception) {
                $this->logger->info("CONTACT: $field is not mergeable for {$this->winner->getId()} - ".$exception->getMessage());
            }
        }

        return $this;
    }

    /**
     * Merge owners if the winner isn't already assigned an owner.
     *
     * @return $this
     * @throws SameContactException
     * @throws MissingMergeSubjectException
     */
    public function mergeOwners()
    {
        $this->checkIfMergeable();

        $oldOwner = $this->winner->getOwner();
        $newOwner = $this->loser->getOwner();

        if ($oldOwner === null && $newOwner !== null) {
            $this->winner->setOwner($newOwner);

            $this->logger->debug("CONTACT: New owner of {$this->winner->getId()} is {$newOwner->getId()}");
        }

        return $this;
    }

    /**
     * Sum points from both contacts.
     *
     * @return $this
     * @throws SameContactException
     * @throws MissingMergeSubjectException
     */
    public function mergePoints()
    {
        $this->checkIfMergeable();

        $winnerPoints = (int) $this->winner->getPoints();
        $loserPoints  = (int) $this->loser->getPoints();
        $this->winner->setPoints($winnerPoints + $loserPoints);
        $this->logger->debug("CONTACT: Adding {$loserPoints} points to {$this->winner->getId()}");

        return $this;
    }

    /**
     * Merge tags from loser into winner.
     *
     * @return $this
     * @throws SameContactException
     * @throws MissingMergeSubjectException
     */
    public function mergeTags()
    {
        $this->checkIfMergeable();

        $loserTags = $this->loser->getTags();
        $addTags   = $loserTags->getKeys();

        $this->leadModel->modifyTags($this->winner, $addTags, null, false);

        return $this;
    }

    /**
     * Merge past merge records into the winner.
     *
     * @return $this
     * @throws SameContactException
     * @throws MissingMergeSubjectException
     */
    private function updateMergeRecords()
    {
        $this->checkIfMergeable();

        // Update merge records for the lead about to be deleted
        $this->repo->moveMergeRecord($this->loser->getId(), $this->winner->getId());

        // Create an entry this contact was merged
        $mergeRecord = new MergeRecord();
        $mergeRecord->setContact($this->winner)
            ->setDateAdded()
            ->setName($this->loser->getPrimaryIdentifier())
            ->setMergedId($this->loser->getId());

        $this->repo->saveEntity($mergeRecord);
        $this->repo->clear();

        return $this;
    }

    /**
     * @throws SameContactException
     * @throws MissingMergeSubjectException
     */
    private function checkIfMergeable()
    {
        if (!$this->winner || !$this->loser) {
            throw new MissingMergeSubjectException();
        }

        if ($this->winner->getId() === $this->loser->getId()) {
            throw new SameContactException();
        }
    }
}
