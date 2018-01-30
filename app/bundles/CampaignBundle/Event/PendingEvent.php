<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\FailedLeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

class PendingEvent extends AbstractLogCollectionEvent
{
    /**
     * @var ArrayCollection
     */
    private $failures;

    /**
     * @var ArrayCollection
     */
    private $successful;

    /**
     * @var string|null
     */
    private $channel;

    /**
     * @var int|null
     */
    private $channelId;

    /**
     * @var \DateTime
     */
    private $now;

    /**
     * PendingEvent constructor.
     *
     * @param AbstractEventAccessor $config
     * @param Event                 $event
     * @param ArrayCollection       $logs
     */
    public function __construct(AbstractEventAccessor $config, Event $event, ArrayCollection $logs)
    {
        $this->failures   = new ArrayCollection();
        $this->successful = new ArrayCollection();
        $this->now        = new \DateTime();

        parent::__construct($config, $event, $logs);
    }

    /**
     * @return ArrayCollection
     */
    public function getPending()
    {
        return $this->logs;
    }

    /**
     * @param LeadEventLog $log
     * @param string       $reason
     */
    public function fail(LeadEventLog $log, $reason)
    {
        if (!$failedLog = $log->getFailedLog()) {
            $failedLog = new FailedLeadEventLog();
        }

        $failedLog->setLog($log)
            ->setDateAdded(new \DateTime())
            ->setReason($reason);

        // Used by the UI
        $metadata = $log->getMetadata();
        $metadata = array_merge(
            $metadata,
            [
                'failed' => 1,
                'reason' => $reason,
            ]
        );
        $log->setMetadata($metadata);

        $this->logChannel($log);

        $this->failures->add($log);
    }

    /**
     * @param $reason
     */
    public function failAll($reason)
    {
        foreach ($this->logs as $log) {
            $this->fail($log, $reason);
        }
    }

    /**
     * @param LeadEventLog $log
     */
    public function pass(LeadEventLog $log)
    {
        if ($failedLog = $log->getFailedLog()) {
            // Delete existing entries
            $failedLog->setLog(null);
            $log->setFailedLog(null);

            $metadata = $log->getMetadata();
            unset($metadata['errors']);
            $log->setMetadata($metadata);
        }
        $this->logChannel($log);
        $log->setIsScheduled(false)
            ->setDateTriggered($this->now);

        $this->successful->add($log);
    }

    /**
     * Pass all pending.
     */
    public function passAll()
    {
        /** @var LeadEventLog $log */
        foreach ($this->logs as $log) {
            $this->pass($log);
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getFailures()
    {
        return $this->failures;
    }

    /**
     * @return ArrayCollection
     */
    public function getSuccessful()
    {
        return $this->successful;
    }

    /**
     * @param      $channel
     * @param null $channelId
     */
    public function setChannel($channel, $channelId = null)
    {
        $this->channel   = $channel;
        $this->channelId = $channelId;
    }

    /**
     * @param LeadEventLog $log
     */
    private function logChannel(LeadEventLog $log)
    {
        if ($this->channel) {
            $log->setChannel($this->channel)
                ->setChannelId($this->channelId);
        }
    }
}
