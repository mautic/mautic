<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\PreferenceBuilder;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Psr\Log\LoggerInterface;

class ChannelPreferences
{
    /**
     * @var
     */
    private $channel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var ArrayCollection[]
     */
    private $organizedByPriority = [];

    /**
     * ChannelPreferences constructor.
     *
     * @param string          $channel
     * @param Event           $event
     * @param LoggerInterface $logger
     */
    public function __construct($channel, Event $event, LoggerInterface $logger)
    {
        $this->channel = $channel;
        $this->logger  = $logger;
        $this->event   = $event;
    }

    /**
     * @param int $priority
     *
     * @return $this
     */
    public function addPriority($priority)
    {
        $priority = (int) $priority;

        if (!isset($this->organizedByPriority[$priority])) {
            $this->organizedByPriority[$priority] = new ArrayCollection();
        }

        return $this;
    }

    /**
     * @param LeadEventLog $log
     * @param int          $priority
     *
     * @return $this
     */
    public function addLog(LeadEventLog $log, $priority)
    {
        $priority = (int) $priority;

        $this->addPriority($priority);

        // We have to clone the log to not affect the original assocaited with the MM event itself

        // Clone to remove from Doctrine's ORM memory since we're having to apply a pseudo event
        $log = clone $log;
        $log->setEvent($this->event);

        $this->organizedByPriority[$priority]->set($log->getId(), $log);

        return $this;
    }

    /**
     * Removes a log from all prioritized groups.
     *
     * @param LeadEventLog $log
     *
     * @return $this
     */
    public function removeLog(LeadEventLog $log)
    {
        /**
         * @var int
         * @var ArrayCollection|LeadEventLog[] $logs
         */
        foreach ($this->organizedByPriority as $priority => $logs) {
            $logs->remove($log->getId());
        }

        return $this;
    }

    /**
     * @param int $priority
     *
     * @return ArrayCollection|LeadEventLog[]
     */
    public function getLogsByPriority($priority)
    {
        $priority = (int) $priority;

        return isset($this->organizedByPriority[$priority]) ? $this->organizedByPriority[$priority] : new ArrayCollection();
    }
}
