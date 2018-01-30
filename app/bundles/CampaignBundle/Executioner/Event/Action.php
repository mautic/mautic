<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\EventDispatcher;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;
use Mautic\CampaignBundle\Helper\ChannelExtractor;
use Mautic\LeadBundle\Entity\Lead;

class Action implements EventInterface
{
    const TYPE = 'action';

    /**
     * @var EventLogger
     */
    private $eventLogger;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * Action constructor.
     *
     * @param EventLogger $eventLogger
     */
    public function __construct(EventLogger $eventLogger, EventDispatcher $dispatcher)
    {
        $this->eventLogger = $eventLogger;
        $this->dispatcher  = $dispatcher;
    }

    /**
     * @param AbstractEventAccessor $config
     * @param Event                 $event
     * @param ArrayCollection       $contacts
     *
     * @return mixed|void
     *
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     */
    public function executeForContacts(AbstractEventAccessor $config, Event $event, ArrayCollection $contacts)
    {
        // Ensure each contact has a log entry to prevent them from being picked up again prematurely
        foreach ($contacts as $contact) {
            $log = $this->getLogEntry($event, $contact);
            ChannelExtractor::setChannel($log, $event, $config);

            $this->eventLogger->addToQueue($log);
        }
        $this->eventLogger->persistQueued();

        // Execute to process the batch of contacts
        $this->dispatcher->executeEvent($config, $event, $this->eventLogger->getLogs());

        // Update log entries or persist failed entries
        $this->eventLogger->persist();
    }

    /**
     * @param AbstractEventAccessor $config
     * @param Event                 $event
     * @param ArrayCollection       $logs
     *
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     */
    public function executeLogs(AbstractEventAccessor $config, Event $event, ArrayCollection $logs)
    {
        // Execute to process the batch of contacts
        $this->dispatcher->executeEvent($config, $event, $logs);

        // Update log entries or persist failed entries
        $this->eventLogger->persistCollection($logs);
    }

    /**
     * @param Event $event
     * @param Lead  $contact
     *
     * @return \Mautic\CampaignBundle\Entity\LeadEventLog
     */
    private function getLogEntry(Event $event, Lead $contact)
    {
        // Create the entry
        $log = $this->eventLogger->buildLogEntry($event, $contact);

        $log->setIsScheduled(false);
        $log->setDateTriggered(new \DateTime());

        $this->eventLogger->persistLog($log);

        return $log;
    }
}
