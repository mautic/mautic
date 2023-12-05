<?php

namespace Mautic\CampaignBundle\Executioner\Logger;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Helper\ChannelExtractor;
use Mautic\CampaignBundle\Model\SummaryModel;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;

class EventLogger
{
    private \Mautic\CoreBundle\Helper\IpLookupHelper $ipLookupHelper;

    private \Mautic\LeadBundle\Tracker\ContactTracker $contactTracker;

    private \Mautic\CampaignBundle\Entity\LeadEventLogRepository $leadEventLogRepository;

    private \Mautic\CampaignBundle\Model\SummaryModel $summaryModel;

    private \Mautic\CampaignBundle\Entity\LeadRepository $leadRepository;

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
     * @var int
     */
    private $lastUsedCampaignIdToFetchRotation;

    public function __construct(
        IpLookupHelper $ipLookupHelper,
        ContactTracker $contactTracker,
        LeadEventLogRepository $leadEventLogRepository,
        LeadRepository $leadRepository,
        SummaryModel $summaryModel
    ) {
        $this->ipLookupHelper         = $ipLookupHelper;
        $this->contactTracker         = $contactTracker;
        $this->leadEventLogRepository = $leadEventLogRepository;
        $this->leadRepository         = $leadRepository;
        $this->summaryModel           = $summaryModel;

        $this->persistQueue = new ArrayCollection();
        $this->logs         = new ArrayCollection();
    }

    public function queueToPersist(LeadEventLog $log)
    {
        $this->persistQueue->add($log);

        if ($this->persistQueue->count() >= 20) {
            $this->persistPendingAndInsertIntoLogStack();
        }
    }

    public function persistLog(LeadEventLog $log)
    {
        $this->leadEventLogRepository->saveEntity($log);
        $this->summaryModel->updateSummary([$log]);
    }

    /**
     * @param bool $isInactiveEvent
     */
    public function buildLogEntry(Event $event, Lead $contact = null, $isInactiveEvent = false): LeadEventLog
    {
        $log = new LeadEventLog();

        if (!defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED')) {
            $log->setIpAddress($this->ipLookupHelper->getIpAddress());
        }

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

        if (isset($this->contactRotations[$contact->getId()]) && ($this->lastUsedCampaignIdToFetchRotation === $event->getCampaign()->getId())) {
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

    public function persistCollection(ArrayCollection $collection): self
    {
        if (!$collection->count()) {
            return $this;
        }

        $this->leadEventLogRepository->saveEntities($collection->getValues());
        $this->summaryModel->updateSummary($collection->getValues());

        return $this;
    }

    public function clearCollection(ArrayCollection $collection): self
    {
        $this->leadEventLogRepository->detachEntities($collection->getValues());

        return $this;
    }

    public function extractContactsFromLogs(ArrayCollection $logs): ArrayCollection
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
     * @param bool $isInactiveEntry
     *
     * @return ArrayCollection
     */
    public function fetchRotationAndGenerateLogsFromContacts(Event $event, AbstractEventAccessor $config, ArrayCollection $contacts, $isInactiveEntry = false)
    {
        $this->hydrateContactRotationsForNewLogs($contacts->getKeys(), $event->getCampaign()->getId());

        return $this->generateLogsFromContacts($event, $config, $contacts, $isInactiveEntry);
    }

    /**
     * @param bool $isInactiveEntry
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
     * @param int $campaignId
     */
    public function hydrateContactRotationsForNewLogs(array $contactIds, $campaignId)
    {
        $this->contactRotations                  = $this->leadRepository->getContactRotations($contactIds, $campaignId);
        $this->lastUsedCampaignIdToFetchRotation = $campaignId;
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

    public function getSummaryModel(): SummaryModel
    {
        return $this->summaryModel;
    }
}
