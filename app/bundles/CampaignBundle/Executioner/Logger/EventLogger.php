<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Logger;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Helper\ChannelExtractor;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;

class EventLogger
{
    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var ContactTracker
     */
    private $contactTracker;

    /**
     * @var LeadEventLogRepository
     */
    private $leadEventLogRepository;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var ArrayCollection
     */
    private $persistQueue;

    /**
     * @var ArrayCollection
     */
    private $logs;

    /**
     * @var array
     */
    private $contactRotations = [];

    /**
     * EventLogger constructor.
     *
     * @param IpLookupHelper         $ipLookupHelper
     * @param ContactTracker         $contactTracker
     * @param LeadEventLogRepository $leadEventLogRepository
     * @param LeadRepository         $leadRepository
     */
    public function __construct(
        IpLookupHelper $ipLookupHelper,
        ContactTracker $contactTracker,
        LeadEventLogRepository $leadEventLogRepository,
        LeadRepository $leadRepository
    ) {
        $this->ipLookupHelper         = $ipLookupHelper;
        $this->contactTracker         = $contactTracker;
        $this->leadEventLogRepository = $leadEventLogRepository;
        $this->leadRepository         = $leadRepository;

        $this->persistQueue = new ArrayCollection();
        $this->logs         = new ArrayCollection();
    }

    /**
     * @param LeadEventLog $log
     */
    public function queueToPersist(LeadEventLog $log)
    {
        $this->persistQueue->add($log);

        if ($this->persistQueue->count() >= 20) {
            $this->persistPendingAndInsertIntoLogStack();
        }
    }

    /**
     * @param LeadEventLog $log
     */
    public function persistLog(LeadEventLog $log)
    {
        $this->leadEventLogRepository->saveEntity($log);
    }

    /**
     * @param Event     $event
     * @param Lead|null $contact
     * @param bool      $isInactiveEvent
     *
     * @return LeadEventLog
     */
    public function buildLogEntry(Event $event, Lead $contact = null, $isInactiveEvent = false)
    {
        $log = new LeadEventLog();

        $log->setIpAddress($this->ipLookupHelper->getIpAddress());

        $log->setEvent($event);
        $log->setCampaign($event->getCampaign());

        if (null === $contact) {
            $contact = $this->contactTracker->getContact();
        }
        $log->setLead($contact);

        if ($isInactiveEvent) {
            $log->setNonActionPathTaken(true);
        }

        $log->setDateTriggered(new \DateTime());
        $log->setSystemTriggered(defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED'));

        if (isset($this->contactRotations[$contact->getId()])) {
            $log->setRotation($this->contactRotations[$contact->getId()]);
        } else {
            // Likely a single contact handle such as decision processing
            $rotations = $this->leadRepository->getContactRotations([$contact->getId()], $event->getCampaign()->getId());
            $log->setRotation($rotations[$contact->getId()]);
        }

        return $log;
    }

    /**
     * Persist the queue, clear the entities from memory, and reset the queue.
     *
     * @return ArrayCollection
     */
    public function persistQueuedLogs()
    {
        $this->persistPendingAndInsertIntoLogStack();

        $logs = clone $this->logs;
        $this->logs->clear();

        return $logs;
    }

    /**
     * @param ArrayCollection $collection
     *
     * @return $this
     */
    public function persistCollection(ArrayCollection $collection)
    {
        if (!$collection->count()) {
            return $this;
        }

        $this->leadEventLogRepository->saveEntities($collection->getValues());

        return $this;
    }

    /**
     * @param ArrayCollection $collection
     *
     * @return $this
     */
    public function clearCollection(ArrayCollection $collection)
    {
        $this->leadEventLogRepository->detachEntities($collection->getValues());

        return $this;
    }

    /**
     * @param ArrayCollection $logs
     *
     * @return ArrayCollection
     */
    public function extractContactsFromLogs(ArrayCollection $logs)
    {
        $contacts = new ArrayCollection();

        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            $contact = $log->getLead();
            $contacts->set($contact->getId(), $contact);
        }

        return $contacts;
    }

    /**
     * @param Event                 $event
     * @param AbstractEventAccessor $config
     * @param ArrayCollection       $contacts
     * @param bool                  $isInactiveEntry
     *
     * @return ArrayCollection
     */
    public function fetchRotationAndGenerateLogsFromContacts(Event $event, AbstractEventAccessor $config, ArrayCollection $contacts, $isInactiveEntry = false)
    {
        $this->hydrateContactRotationsForNewLogs($contacts->getKeys(), $event->getCampaign()->getId());

        return $this->generateLogsFromContacts($event, $config, $contacts, $isInactiveEntry);
    }

    /**
     * @param Event                 $event
     * @param AbstractEventAccessor $config
     * @param ArrayCollection       $contacts
     * @param bool                  $isInactiveEntry
     *
     * @return ArrayCollection
     */
    public function generateLogsFromContacts(Event $event, AbstractEventAccessor $config, ArrayCollection $contacts, $isInactiveEntry)
    {
        $isDecision = Event::TYPE_DECISION === $event->getEventType();

        // Ensure each contact has a log entry to prevent them from being picked up again prematurely
        foreach ($contacts as $contact) {
            $log = $this->buildLogEntry($event, $contact, $isInactiveEntry);
            $log->setIsScheduled(false);
            $log->setDateTriggered(new \DateTime());
            ChannelExtractor::setChannel($log, $event, $config);

            if ($isDecision) {
                // Do not pre-persist decision logs as they must be evaluated first
                $this->logs->add($log);
            } else {
                $this->queueToPersist($log);
            }
        }

        return $this->persistQueuedLogs();
    }

    /**
     * @param array $contactIds
     * @param int   $campaignId
     */
    public function hydrateContactRotationsForNewLogs(array $contactIds, $campaignId)
    {
        $this->contactRotations = $this->leadRepository->getContactRotations($contactIds, $campaignId);
    }

    private function persistPendingAndInsertIntoLogStack()
    {
        if (!$this->persistQueue->count()) {
            return;
        }

        $this->leadEventLogRepository->saveEntities($this->persistQueue->getValues());

        // Push them into the logs ArrayCollection to be used later.
        /** @var LeadEventLog $log */
        foreach ($this->persistQueue as $log) {
            $this->logs->set($log->getId(), $log);
        }

        $this->persistQueue->clear();
    }
}
