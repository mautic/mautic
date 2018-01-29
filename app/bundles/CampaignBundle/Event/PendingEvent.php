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

class PendingEvent extends \Symfony\Component\EventDispatcher\Event
{
    /**
     * @var AbstractEventAccessor
     */
    private $config;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var ArrayCollection
     */
    private $pending;

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
     * PendingEvent constructor.
     *
     * @param AbstractEventAccessor $config
     * @param Event                 $event
     * @param ArrayCollection       $pending
     */
    public function __construct(AbstractEventAccessor $config, Event $event, ArrayCollection $pending)
    {
        $this->config  = $config;
        $this->event   = $event;
        $this->pending = $pending;

        $this->failures   = new ArrayCollection();
        $this->successful = new ArrayCollection();
    }

    /**
     * @return AbstractEventAccessor
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return ArrayCollection
     */
    public function getPending()
    {
        return $this->pending;
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
        foreach ($this->pending as $log) {
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
        $this->successful->add($log);
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
     * Check if an event is applicable.
     *
     * @param $eventType
     */
    public function checkContext($eventType)
    {
        return strtolower($eventType) === strtolower($this->event->getType());
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
