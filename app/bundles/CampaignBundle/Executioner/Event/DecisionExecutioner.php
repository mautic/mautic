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
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;
use Mautic\CampaignBundle\Executioner\Dispatcher\DecisionDispatcher;
use Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException;
use Mautic\CampaignBundle\Executioner\Exception\DecisionNotApplicableException;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;
use Mautic\LeadBundle\Entity\Lead;

class DecisionExecutioner implements EventInterface
{
    const TYPE = 'decision';

    /**
     * @var EventLogger
     */
    private $eventLogger;

    /**
     * @var DecisionDispatcher
     */
    private $dispatcher;

    /**
     * DecisionExecutioner constructor.
     *
     * @param EventLogger        $eventLogger
     * @param DecisionDispatcher $dispatcher
     */
    public function __construct(EventLogger $eventLogger, DecisionDispatcher $dispatcher)
    {
        $this->eventLogger = $eventLogger;
        $this->dispatcher  = $dispatcher;
    }

    /**
     * @param DecisionAccessor $config
     * @param Event            $event
     * @param Lead             $contact
     * @param mixed            $passthrough
     * @param string|null      $channel
     * @param int|null         $channelId
     *
     * @throws CannotProcessEventException
     * @throws DecisionNotApplicableException
     */
    public function evaluateForContact(DecisionAccessor $config, Event $event, Lead $contact, $passthrough = null, $channel = null, $channelId = null)
    {
        if (Event::TYPE_DECISION !== $event->getEventType()) {
            throw new CannotProcessEventException('Cannot process event ID '.$event->getId().' as a decision.');
        }

        $log = $this->eventLogger->buildLogEntry($event, $contact);
        $log->setChannel($channel)
            ->setChannelId($channelId);

        $decisionEvent = $this->dispatcher->dispatchRealTimeEvent($config, $log, $passthrough);

        if (!$decisionEvent->wasDecisionApplicable()) {
            throw new DecisionNotApplicableException('evaluation failed');
        }

        $this->eventLogger->persistLog($log);
    }

    /**
     * @param AbstractEventAccessor $config
     * @param ArrayCollection       $logs
     *
     * @return EvaluatedContacts
     *
     * @throws CannotProcessEventException
     */
    public function execute(AbstractEventAccessor $config, ArrayCollection $logs)
    {
        $evaluatedContacts = new EvaluatedContacts();
        $failedLogs        = [];

        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            if (Event::TYPE_DECISION !== $log->getEvent()->getEventType()) {
                throw new CannotProcessEventException('Event ID '.$log->getEvent()->getId().' is not a decision');
            }

            try {
                /* @var DecisionAccessor $config */
                $this->dispatchEvent($config, $log);
                $evaluatedContacts->pass($log->getLead());

                // Update the date triggered timestamp
                $log->setDateTriggered(new \DateTime());
            } catch (DecisionNotApplicableException $exception) {
                // Fail the contact but remove the log from being processed upstream
                // active/positive/green path while letting the InactiveExecutioner handle the inactive/negative/red paths
                $failedLogs[] = $log;
                $evaluatedContacts->fail($log->getLead());
            }
        }

        $this->dispatcher->dispatchDecisionResultsEvent($config, $logs, $evaluatedContacts);

        // Remove the logs
        foreach ($failedLogs as $log) {
            $logs->removeElement($log);
        }

        return $evaluatedContacts;
    }

    /**
     * @param DecisionAccessor $config
     * @param LeadEventLog     $log
     * @param mixed            $passthrough
     *
     * @throws DecisionNotApplicableException
     */
    private function dispatchEvent(DecisionAccessor $config, LeadEventLog $log, $passthrough = null)
    {
        $decisionEvent = $this->dispatcher->dispatchEvaluationEvent($config, $log, $passthrough);

        if (!$decisionEvent->wasDecisionApplicable()) {
            throw new DecisionNotApplicableException('evaluation failed');
        }
    }
}
