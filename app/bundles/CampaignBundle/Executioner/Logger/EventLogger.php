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
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\Model\LeadModel;

class EventLogger
{
    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var LeadEventLogRepository
     */
    private $repo;

    /**
     * @var ArrayCollection
     */
    private $queued;

    /**
     * @var ArrayCollection
     */
    private $processed;

    /**
     * LogHelper constructor.
     *
     * @param IpLookupHelper         $ipLookupHelper
     * @param LeadModel              $leadModel
     * @param LeadEventLogRepository $repo
     */
    public function __construct(IpLookupHelper $ipLookupHelper, LeadModel $leadModel, LeadEventLogRepository $repo)
    {
        $this->ipLookupHelper = $ipLookupHelper;
        $this->leadModel      = $leadModel;
        $this->repo           = $repo;

        $this->queued    = new ArrayCollection();
        $this->processed = new ArrayCollection();
    }

    /**
     * @param LeadEventLog $log
     */
    public function addToQueue(LeadEventLog $log)
    {
        $this->queued->add($log);

        if ($this->queued->count() >= 20) {
            $this->persistQueued();
        }
    }

    /**
     * @param LeadEventLog $log
     */
    public function persistLog(LeadEventLog $log)
    {
        $this->repo->saveEntity($log);
    }

    /**
     * @param Event $event
     * @param null  $lead
     *
     * @return LeadEventLog
     */
    public function buildLogEntry(Event $event, $lead = null)
    {
        $log = new LeadEventLog();

        $log->setIpAddress($this->ipLookupHelper->getIpAddress());

        $log->setEvent($event);

        if ($lead == null) {
            $lead = $this->leadModel->getCurrentLead();
        }
        $log->setLead($lead);

        $log->setDateTriggered(new \DateTime());
        $log->setSystemTriggered(defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED'));

        return $log;
    }

    /**
     * Persist the queue, clear the entities from memory, and reset the queue.
     */
    public function persistQueued()
    {
        if ($this->queued->count()) {
            $this->repo->saveEntities($this->queued->getValues());
        }

        // Push them into the processed ArrayCollection to be used later.
        /** @var LeadEventLog $log */
        foreach ($this->queued as $log) {
            $this->processed->set($log->getId(), $log);
        }

        $this->queued->clear();
    }

    /**
     * @return ArrayCollection
     */
    public function getLogs()
    {
        return $this->processed;
    }

    /**
     * @param ArrayCollection $collection
     */
    public function persistCollection(ArrayCollection $collection)
    {
        if (!$collection->count()) {
            return;
        }

        $this->repo->saveEntities($collection->getValues());
        $this->repo->clear();

        // Clear queued and processed
        $this->processed->clear();
        $this->queued->clear();
    }

    /**
     * Persist processed entities after they've been updated.
     */
    public function persist()
    {
        if (!$this->processed->count()) {
            return;
        }

        $this->repo->saveEntities($this->processed->getValues());
        $this->processed->clear();
        $this->repo->clear();
    }
}
