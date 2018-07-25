<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Deduplicate;

use Mautic\LeadBundle\Deduplicate\Exception\SameContactException;
use Mautic\LeadBundle\Deduplicate\Exception\ValueNotMergeableException;
use Mautic\LeadBundle\Deduplicate\Helper\MergeValueHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\MergeRecord;
use Mautic\LeadBundle\Entity\MergeRecordRepository;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContactMerger
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
     * ContactMerger constructor.
     *
     * @param LeadModel                $leadModel
     * @param MergeRecordRepository    $repo
     * @param EventDispatcherInterface $dispatcher
     * @param LoggerInterface          $logger
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
     * @param Lead $loser
     *
     * @return Lead
     *
     * @throws SameContactException
     */
    public function merge(Lead $winner, Lead $loser)
    {
        if ($winner->getId() === $loser->getId()) {
            throw new SameContactException();
        }

        $this->logger->debug('CONTACT: ID# '.$loser->getId().' will be merged into ID# '.$winner->getId());

        // Dispatch pre merge event
        $event = new LeadMergeEvent($winner, $loser);
        $this->dispatcher->dispatch(LeadEvents::LEAD_PRE_MERGE, $event);

        // Merge everything
        $this->updateMergeRecords($winner, $loser)
            ->mergeTimestamps($winner, $loser)
            ->mergeIpAddressHistory($winner, $loser)
            ->mergeFieldData($winner, $loser)
            ->mergeOwners($winner, $loser)
            ->mergePoints($winner, $loser)
            ->mergeTags($winner, $loser);

        // Save the updated contact
        $this->leadModel->saveEntity($winner, false);

        // Dispatch post merge event
        $this->dispatcher->dispatch(LeadEvents::LEAD_POST_MERGE, $event);

        // Delete the loser
        $this->leadModel->deleteEntity($loser);

        return $winner;
    }

    /**
     * Merge timestamps.
     *
     * @param Lead $winner
     * @param Lead $loser
     *
     * @return $this
     */
    public function mergeTimestamps(Lead $winner, Lead $loser)
    {
        // The winner should keep the most recent last active timestamp of the two
        if ($loser->getLastActive() > $winner->getLastActive()) {
            $winner->setLastActive($loser->getLastActive());
        }

        /*
         * The winner should keep the oldest date identified timestamp
         * as long as the loser's date identified is not null.
         * Alternatively, if the winner's date identified is null,
         * use the loser's date identified (doesn't matter if it is null).
         */
        if (($loser->getDateIdentified() !== null && $loser->getDateIdentified() < $winner->getDateIdentified()) || $winner->getDateIdentified() === null) {
            $winner->setDateIdentified($loser->getDateIdentified());
        }

        return $this;
    }

    /**
     * Merge IP history into the winner.
     *
     * @param Lead $winner
     * @param Lead $loser
     *
     * @return $this
     */
    public function mergeIpAddressHistory(Lead $winner, Lead $loser)
    {
        $ipAddresses = $loser->getIpAddresses();

        foreach ($ipAddresses as $ip) {
            $winner->addIpAddress($ip);

            $this->logger->debug('CONTACT: Associating '.$winner->getId().' with IP '.$ip->getIpAddress());
        }

        return $this;
    }

    /**
     * Merge custom field data into winner.
     *
     * @param Lead $winner
     * @param Lead $loser
     *
     * @return $this
     */
    public function mergeFieldData(Lead $winner, Lead $loser)
    {
        // Use the modified date if applicable or date added if the contact has never been edited
        $loserDate  = ($loser->getDateModified()) ? $loser->getDateModified() : $loser->getDateAdded();
        $winnerDate = ($winner->getDateModified()) ? $winner->getDateModified() : $winner->getDateAdded();

        // When it comes to data, keep the newest value regardless of the winner/loser
        $newest = ($loserDate > $winnerDate) ? $loser : $winner;
        $oldest = ($newest->getId() === $winner->getId()) ? $loser : $winner;

        // It may happen that the Lead entities doesn't have fields fill in. Fill them in if not.
        if (!$newest->hasFields()) {
            $newest->setFields($this->leadModel->getRepository()->getFieldValues($newest->getId()));
        }

        if (!$oldest->hasFields()) {
            $oldest->setFields($this->leadModel->getRepository()->getFieldValues($oldest->getId()));
        }

        $newestFields = $newest->getProfileFields();
        $oldestFields = $oldest->getProfileFields();

        foreach (array_keys($newestFields) as $field) {
            if (in_array($field, ['id', 'points'])) {
                // Let mergePoints() take care of this
                continue;
            }

            try {
                $newValue = MergeValueHelper::getMergeValue($newestFields[$field], $oldestFields[$field], $winner->getFieldValue($field));
                $winner->addUpdatedField($field, $newValue);

                $fromValue = empty($oldestFields[$field]) ? 'empty' : $oldestFields[$field];
                $this->logger->debug("CONTACT: Updated $field from $fromValue to $newValue for {$winner->getId()}");
            } catch (ValueNotMergeableException $exception) {
                $this->logger->info("CONTACT: $field is not mergeable for {$winner->getId()} - ".$exception->getMessage());
            }
        }

        return $this;
    }

    /**
     * Merge owners if the winner isn't already assigned an owner.
     *
     * @param Lead $winner
     * @param Lead $loser
     *
     * @return $this
     */
    public function mergeOwners(Lead $winner, Lead $loser)
    {
        $oldOwner = $winner->getOwner();
        $newOwner = $loser->getOwner();

        if ($oldOwner === null && $newOwner !== null) {
            $winner->setOwner($newOwner);

            $this->logger->debug("CONTACT: New owner of {$winner->getId()} is {$newOwner->getId()}");
        }

        return $this;
    }

    /**
     * Sum points from both contacts.
     *
     * @param Lead $winner
     * @param Lead $loser
     *
     * @return $this
     */
    public function mergePoints(Lead $winner, Lead $loser)
    {
        $winnerPoints = (int) $winner->getPoints();
        $loserPoints  = (int) $loser->getPoints();
        $winner->adjustPoints($loserPoints);

        $this->logger->debug(
            'CONTACT: Adding '.$loserPoints.' points from contact ID #'.$loser->getId().' to contact ID #'.$winner->getId().' with '.$winnerPoints
            .' points'
        );

        return $this;
    }

    /**
     * Merge tags from loser into winner.
     *
     * @param Lead $winner
     * @param Lead $loser
     *
     * @return $this
     */
    public function mergeTags(Lead $winner, Lead $loser)
    {
        $loserTags = $loser->getTags();
        $addTags   = $loserTags->getKeys();

        $this->leadModel->modifyTags($winner, $addTags, null, false);

        return $this;
    }

    /**
     * Merge past merge records into the winner.
     *
     * @param Lead $winner
     * @param Lead $loser
     *
     * @return $this
     */
    private function updateMergeRecords(Lead $winner, Lead $loser)
    {
        // Update merge records for the lead about to be deleted
        $this->repo->moveMergeRecord($loser->getId(), $winner->getId());

        // Create an entry this contact was merged
        $mergeRecord = new MergeRecord();
        $mergeRecord->setContact($winner)
            ->setDateAdded()
            ->setName($loser->getPrimaryIdentifier())
            ->setMergedId($loser->getId());

        $this->repo->saveEntity($mergeRecord);
        $this->repo->clear();

        return $this;
    }
}
