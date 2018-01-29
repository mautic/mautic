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
    public function addToQueue(LeadEventLog $log, $clearPostPersist = true)
    {
        $this->queued->add($log);

        if ($this->queued->count() >= 20) {
            $this->persistQueued($clearPostPersist);
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
     * @param bool  $systemTriggered
     *
     * @return LeadEventLog
     */
    public function buildLogEntry(Event $event, $lead = null, $systemTriggered = false)
    {
        $log = new LeadEventLog();

        $log->setIpAddress($this->ipLookupHelper->getIpAddress());

        $log->setEvent($event);

        if ($lead == null) {
            $lead = $this->leadModel->getCurrentLead();
        }
        $log->setLead($lead);

        $log->setDateTriggered(new \DateTime());
        $log->setSystemTriggered($systemTriggered);

        return $log;
    }

    /**
     * Persist the queue, clear the entities from memory, and reset the queue.
     */
    public function persistQueued($clearPostPersist = true)
    {
        if ($this->queued) {
            $this->repo->saveEntities($this->queued->getValues());

            if ($clearPostPersist) {
                $this->repo->clear();
            }
        }

        if (!$clearPostPersist) {
            // The logs are needed so don't clear
            foreach ($this->queued as $log) {
                $this->processed->add($log);
            }
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
     * Persist processed entities after they've been updated.
     */
    public function persist()
    {
        if (!$this->processed) {
            return;
        }

        $this->repo->saveEntities($this->processed->getValues());
        $this->repo->clear();
        $this->processed->clear();
    }
}
