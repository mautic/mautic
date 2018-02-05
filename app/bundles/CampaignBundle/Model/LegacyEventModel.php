<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Model;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityNotFoundException;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\FailedLeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Event\CampaignScheduledEvent;
use Mautic\CampaignBundle\Executioner\DecisionExecutioner;
use Mautic\CampaignBundle\Executioner\KickoffExecutioner;
use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;

/**
 * @deprecated 2.13.0 to be removed in 3.0
 */
class LegacyEventModel extends CommonFormModel
{
    /**
     * @var DecisionExecutioner
     */
    private $decisionExecutioner;

    /**
     * @var KickoffExecutioner
     */
    private $kickoffExecutioner;

    /**
     * @var ScheduledExecutioner
     */
    private $scheduledExecutioner;

    /**
     * @var
     */
    protected $triggeredEvents;

    /**
     * LegacyEventModel constructor.
     *
     * @param DecisionExecutioner  $decisionExecutioner
     * @param KickoffExecutioner   $kickoffExecutioner
     * @param ScheduledExecutioner $scheduledExecutioner
     */
    public function __construct(
        DecisionExecutioner $decisionExecutioner,
        KickoffExecutioner $kickoffExecutioner,
        ScheduledExecutioner $scheduledExecutioner
    ) {
        $this->decisionExecutioner  = $decisionExecutioner;
        $this->kickoffExecutioner   = $kickoffExecutioner;
        $this->scheduledExecutioner = $scheduledExecutioner;
    }

    /**
     * Trigger the root level action(s) in campaign(s).
     *
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param Campaign             $campaign
     * @param                      $totalEventCount
     * @param int                  $limit
     * @param bool                 $max
     * @param OutputInterface|null $output
     * @param null                 $leadId
     * @param bool                 $returnCounts
     *
     * @return array|int
     *
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException
     */
    public function triggerStartingEvents(
        Campaign $campaign,
        &$totalEventCount,
        $limit = 100,
        $max = false,
        OutputInterface $output = null,
        $leadId = null,
        $returnCounts = false
    ) {
        if ($leadId) {
            $counter = $this->kickoffExecutioner->executeForContact($campaign, $leadId, $output);
        } else {
            $counter = $this->kickoffExecutioner->executeForCampaign($campaign, $limit, $output);
        }

        $totalEventCount += $counter->getEventCount();

        if ($returnCounts) {
            return [
                'events'         => $counter->getEventCount(),
                'evaluated'      => $counter->getEvaluated(),
                'executed'       => $counter->getExecuted(),
                'totalEvaluated' => $counter->getTotalEvaluated(),
                'totalExecuted'  => $counter->getTotalExecuted(),
            ];
        }

        return $counter->getTotalExecuted();
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param                      $campaign
     * @param                      $totalEventCount
     * @param int                  $limit
     * @param bool                 $max
     * @param OutputInterface|null $output
     * @param bool                 $returnCounts
     *
     * @return array|int
     *
     * @throws \Doctrine\ORM\Query\QueryException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException
     */
    public function triggerScheduledEvents(
        $campaign,
        &$totalEventCount,
        $limit = 100,
        $max = false,
        OutputInterface $output = null,
        $returnCounts = false
    ) {
        $counter = $this->scheduledExecutioner->executeForCampaign($campaign, $limit, $output);

        $totalEventCount += $counter->getEventCount();

        if ($returnCounts) {
            return [
                'events'         => $counter->getEventCount(),
                'evaluated'      => $counter->getEvaluated(),
                'executed'       => $counter->getExecuted(),
                'totalEvaluated' => $counter->getTotalEvaluated(),
                'totalExecuted'  => $counter->getTotalExecuted(),
            ];
        }

        return $counter->getTotalExecuted();
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param      $type
     * @param null $eventDetails
     * @param null $channel
     * @param null $channelId
     *
     * @return array
     *
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogNotProcessedException
     * @throws \Mautic\CampaignBundle\Executioner\Dispatcher\Exception\LogPassedAndFailedException
     * @throws \Mautic\CampaignBundle\Executioner\Exception\CannotProcessEventException
     * @throws \Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException
     */
    public function triggerEvent($type, $eventDetails = null, $channel = null, $channelId = null)
    {
        $response = $this->decisionExecutioner->execute($type, $eventDetails, $channel, $channelId);

        return $response->getResponseArray();
    }

    /**
     * Handles condition type events.
     *
     * @deprecated 2.6.0 to be removed in 3.0
     *
     * @param bool     $response
     * @param array    $eventSettings
     * @param array    $event
     * @param Campaign $campaign
     * @param Lead     $lead
     * @param int      $evaluatedEventCount The number of events evaluated for the current method (kickoff, negative/inaction, scheduled)
     * @param int      $executedEventCount  The number of events successfully executed for the current method
     * @param int      $totalEventCount     The total number of events across all methods
     *
     * @return bool True if an event was executed
     */
    public function handleCondition(
        $response,
        $eventSettings,
        $event,
        $campaign,
        $lead,
        &$evaluatedEventCount = 0,
        &$executedEventCount = 0,
        &$totalEventCount = 0
    ) {
        $repo         = $this->getRepository();
        $decisionPath = ($response === true) ? 'yes' : 'no';
        $childEvents  = $repo->getEventsByParent($event['id'], $decisionPath);

        $this->logger->debug(
            'CAMPAIGN: Condition ID# '.$event['id'].' triggered with '.$decisionPath.' decision path. Has '.count($childEvents).' child event(s).'
        );

        $childExecuted = false;
        foreach ($childEvents as $childEvent) {
            // Trigger child event recursively
            if ($this->executeEvent(
                $childEvent,
                $campaign,
                $lead,
                $eventSettings,
                true,
                null,
                null,
                false,
                $evaluatedEventCount,
                $executedEventCount,
                $totalEventCount
            )
            ) {
                $childExecuted = true;
            }
        }

        return $childExecuted;
    }

    /**
     * Invoke the event's callback function.
     *
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param              $event
     * @param              $settings
     * @param null         $lead
     * @param null         $eventDetails
     * @param bool         $systemTriggered
     * @param LeadEventLog $log
     *
     * @return bool|mixed
     */
    public function invokeEventCallback($event, $settings, $lead = null, $eventDetails = null, $systemTriggered = false, LeadEventLog $log = null)
    {
        if (isset($settings['eventName'])) {
            // Create a campaign event with a default successful result
            $campaignEvent = new CampaignExecutionEvent(
                [
                    'eventSettings'   => $settings,
                    'eventDetails'    => $eventDetails,
                    'event'           => $event,
                    'lead'            => $lead,
                    'systemTriggered' => $systemTriggered,
                    'config'          => $event['properties'],
                ],
                true,
                $log
            );

            $eventName = array_key_exists('eventName', $settings) ? $settings['eventName'] : null;

            if ($eventName && $this->dispatcher->hasListeners($eventName)) {
                $this->dispatcher->dispatch($eventName, $campaignEvent);

                if ($event['eventType'] !== 'decision' && $this->dispatcher->hasListeners(CampaignEvents::ON_EVENT_EXECUTION)) {
                    $this->dispatcher->dispatch(CampaignEvents::ON_EVENT_EXECUTION, $campaignEvent);
                }

                if ($campaignEvent->wasLogUpdatedByListener()) {
                    $campaignEvent->setResult($campaignEvent->getLogEntry());
                }
            }

            if (null !== $log) {
                $log->setChannel($campaignEvent->getChannel())
                    ->setChannelId($campaignEvent->getChannelId());
            }

            return $campaignEvent->getResult();
        }

        /*
         * @deprecated 2.0 - to be removed in 3.0; Use the new eventName method instead
         */
        if (isset($settings['callback']) && is_callable($settings['callback'])) {
            $args = [
                'eventSettings'   => $settings,
                'eventDetails'    => $eventDetails,
                'event'           => $event,
                'lead'            => $lead,
                'factory'         => $this->factory,
                'systemTriggered' => $systemTriggered,
                'config'          => $event['properties'],
            ];

            if (is_array($settings['callback'])) {
                $reflection = new \ReflectionMethod($settings['callback'][0], $settings['callback'][1]);
            } elseif (strpos($settings['callback'], '::') !== false) {
                $parts      = explode('::', $settings['callback']);
                $reflection = new \ReflectionMethod($parts[0], $parts[1]);
            } else {
                $reflection = new \ReflectionMethod(null, $settings['callback']);
            }

            $pass = [];
            foreach ($reflection->getParameters() as $param) {
                if (isset($args[$param->getName()])) {
                    $pass[] = $args[$param->getName()];
                } else {
                    $pass[] = null;
                }
            }

            $result = $reflection->invokeArgs($this, $pass);

            if ('decision' != $event['eventType'] && $this->dispatcher->hasListeners(CampaignEvents::ON_EVENT_EXECUTION)) {
                $executionEvent = $this->dispatcher->dispatch(
                    CampaignEvents::ON_EVENT_EXECUTION,
                    new CampaignExecutionEvent($args, $result, $log)
                );

                if ($executionEvent->wasLogUpdatedByListener()) {
                    $result = $executionEvent->getLogEntry();
                }
            }
        } else {
            $result = true;
        }

        return $result;
    }

    /**
     * Execute or schedule an event. Condition events are executed recursively.
     *
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param array          $event
     * @param Campaign       $campaign
     * @param Lead           $lead
     * @param array          $eventSettings
     * @param bool           $allowNegative
     * @param \DateTime      $parentTriggeredDate
     * @param \DateTime|bool $eventTriggerDate
     * @param bool           $logExists
     * @param int            $evaluatedEventCount The number of events evaluated for the current method (kickoff, negative/inaction, scheduled)
     * @param int            $executedEventCount  The number of events successfully executed for the current method
     * @param int            $totalEventCount     The total number of events across all methods
     *
     * @return bool
     */
    public function executeEvent(
        $event,
        $campaign,
        $lead,
        $eventSettings = null,
        $allowNegative = false,
        \DateTime $parentTriggeredDate = null,
        $eventTriggerDate = null,
        $logExists = false,
        &$evaluatedEventCount = 0,
        &$executedEventCount = 0,
        &$totalEventCount = 0
    ) {
        ++$evaluatedEventCount;
        ++$totalEventCount;

        // Get event settings if applicable
        if ($eventSettings === null) {
            $eventSettings = $this->campaignModel->getEvents();
        }

        // Set date timing should be compared with if applicable
        if ($parentTriggeredDate === null) {
            // Default to today
            $parentTriggeredDate = new \DateTime();
        }

        $repo    = $this->getRepository();
        $logRepo = $this->getLeadEventLogRepository();

        if (isset($eventSettings[$event['eventType']][$event['type']])) {
            $thisEventSettings = $eventSettings[$event['eventType']][$event['type']];
        } else {
            $this->logger->debug(
                'CAMPAIGN: Settings not found for '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
            );
            unset($event);

            return false;
        }

        if ($event['eventType'] == 'condition') {
            $allowNegative = true;
        }

        // Set campaign ID

        $event['campaign'] = [
            'id'        => $campaign->getId(),
            'name'      => $campaign->getName(),
            'createdBy' => $campaign->getCreatedBy(),
        ];

        // Ensure properties is an array
        if ($event['properties'] === null) {
            $event['properties'] = [];
        } elseif (!is_array($event['properties'])) {
            $event['properties'] = unserialize($event['properties']);
        }

        // Ensure triggerDate is a \DateTime
        if ($event['triggerMode'] == 'date' && !$event['triggerDate'] instanceof \DateTime) {
            $triggerDate          = new DateTimeHelper($event['triggerDate']);
            $event['triggerDate'] = $triggerDate->getDateTime();
            unset($triggerDate);
        }

        if ($eventTriggerDate == null) {
            $eventTriggerDate = $this->checkEventTiming($event, $parentTriggeredDate, $allowNegative);
        }
        $result = true;

        // Create/get log entry
        if ($logExists) {
            if (true === $logExists) {
                $log = $logRepo->findOneBy(
                    [
                        'lead'  => $lead->getId(),
                        'event' => $event['id'],
                    ]
                );
            } else {
                $log = $this->em->getReference('MauticCampaignBundle:LeadEventLog', $logExists);
            }
        }

        if (empty($log)) {
            $log = $this->getLogEntity($event['id'], $campaign, $lead, null, !defined('MAUTIC_CAMPAIGN_NOT_SYSTEM_TRIGGERED'));
        }

        if ($eventTriggerDate instanceof \DateTime) {
            ++$executedEventCount;

            $log->setTriggerDate($eventTriggerDate);
            $logRepo->saveEntity($log);

            //lead actively triggered this event, a decision wasn't involved, or it was system triggered and a "no" path so schedule the event to be fired at the defined time
            $this->logger->debug(
                'CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
                .' has timing that is not appropriate and thus scheduled for '.$eventTriggerDate->format('Y-m-d H:m:i T')
            );

            if ($this->dispatcher->hasListeners(CampaignEvents::ON_EVENT_SCHEDULED)) {
                $args = [
                    'eventSettings'   => $thisEventSettings,
                    'eventDetails'    => null,
                    'event'           => $event,
                    'lead'            => $lead,
                    'systemTriggered' => true,
                    'dateScheduled'   => $eventTriggerDate,
                ];

                $scheduledEvent = new CampaignScheduledEvent($args);
                $this->dispatcher->dispatch(CampaignEvents::ON_EVENT_SCHEDULED, $scheduledEvent);
                unset($scheduledEvent, $args);
            }
        } elseif ($eventTriggerDate) {
            // If log already existed, assume it was scheduled in order to not force
            // Doctrine to do a query to fetch the information
            $wasScheduled = (!$logExists) ? $log->getIsScheduled() : true;

            $log->setIsScheduled(false);
            $log->setDateTriggered(new \DateTime());

            try {
                // Save before executing event to ensure it's not picked up again
                $logRepo->saveEntity($log);
                $this->logger->debug(
                    'CAMPAIGN: Created log for '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
                    .' prior to execution.'
                );
            } catch (EntityNotFoundException $exception) {
                // The lead has been likely removed from this lead/list
                $this->logger->debug(
                    'CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
                    .' wasn\'t found: '.$exception->getMessage()
                );

                return false;
            } catch (DBALException $exception) {
                $this->logger->debug(
                    'CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
                    .' failed with DB error: '.$exception->getMessage()
                );

                return false;
            }

            // Set the channel
            $this->campaignModel->setChannelFromEventProperties($log, $event, $thisEventSettings);

            //trigger the action
            $response = $this->invokeEventCallback($event, $thisEventSettings, $lead, null, true, $log);

            // Check if the lead wasn't deleted during the event callback
            if (null === $lead->getId() && $response === true) {
                ++$executedEventCount;

                $this->logger->debug(
                    'CAMPAIGN: Contact was deleted while executing '.ucfirst($event['eventType']).' ID# '.$event['id']
                );

                return true;
            }

            $eventTriggered = false;
            if ($response instanceof LeadEventLog) {
                $log = $response;

                // Listener handled the event and returned a log entry
                $this->campaignModel->setChannelFromEventProperties($log, $event, $thisEventSettings);

                ++$executedEventCount;

                $this->logger->debug(
                    'CAMPAIGN: Listener handled event for '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
                );

                if (!$log->getIsScheduled()) {
                    $eventTriggered = true;
                }
            } elseif (($response === false || (is_array($response) && isset($response['result']) && false === $response['result']))
                && $event['eventType'] == 'action'
            ) {
                $result = false;
                $debug  = 'CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
                    .' failed with a response of '.var_export($response, true);

                // Something failed
                if ($wasScheduled || !empty($this->scheduleTimeForFailedEvents)) {
                    $date = new \DateTime();
                    $date->add(new \DateInterval($this->scheduleTimeForFailedEvents));

                    // Reschedule
                    $log->setTriggerDate($date);

                    if (is_array($response)) {
                        $log->setMetadata($response);
                    }
                    $debug .= ' thus placed on hold '.$this->scheduleTimeForFailedEvents;

                    $metadata = $log->getMetadata();
                    if (is_array($response)) {
                        $metadata = array_merge($metadata, $response);
                    }

                    $reason = null;
                    if (isset($metadata['errors'])) {
                        $reason = (is_array($metadata['errors'])) ? implode('<br />', $metadata['errors']) : $metadata['errors'];
                    } elseif (isset($metadata['reason'])) {
                        $reason = $metadata['reason'];
                    }
                    $this->setEventStatus($log, false, $reason);
                } else {
                    // Remove
                    $debug .= ' thus deleted';
                    $repo->deleteEntity($log);
                    unset($log);
                }

                $this->notifyOfFailure($lead, $campaign->getCreatedBy(), $campaign->getName().' / '.$event['name']);
                $this->logger->debug($debug);
            } else {
                $this->setEventStatus($log, true);

                ++$executedEventCount;

                if ($response !== true) {
                    if ($this->triggeredResponses !== false) {
                        $eventTypeKey = $event['eventType'];
                        $typeKey      = $event['type'];

                        if (!array_key_exists($eventTypeKey, $this->triggeredResponses) || !is_array($this->triggeredResponses[$eventTypeKey])) {
                            $this->triggeredResponses[$eventTypeKey] = [];
                        }

                        if (!array_key_exists($typeKey, $this->triggeredResponses[$eventTypeKey])
                            || !is_array(
                                $this->triggeredResponses[$eventTypeKey][$typeKey]
                            )
                        ) {
                            $this->triggeredResponses[$eventTypeKey][$typeKey] = [];
                        }

                        $this->triggeredResponses[$eventTypeKey][$typeKey][$event['id']] = $response;
                    }

                    $log->setMetadata($response);
                }

                $this->logger->debug(
                    'CAMPAIGN: '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '.$lead->getId()
                    .' successfully executed and logged with a response of '.var_export($response, true)
                );

                $eventTriggered = true;
            }

            if ($eventTriggered) {
                // Collect the events that were triggered so that conditions can be handled properly

                if ('condition' === $event['eventType']) {
                    // Conditions will need child event processed
                    $decisionPath = ($response === true) ? 'yes' : 'no';

                    if (true !== $response) {
                        // Note that a condition took non action path so we can generate a visual stat
                        $log->setNonActionPathTaken(true);
                    }
                } else {
                    // Actions will need to check if conditions are attached to it
                    $decisionPath = 'null';
                }

                if (!isset($this->triggeredEvents[$event['id']])) {
                    $this->triggeredEvents[$event['id']] = [];
                }
                if (!isset($this->triggeredEvents[$event['id']][$decisionPath])) {
                    $this->triggeredEvents[$event['id']][$decisionPath] = [];
                }

                $this->triggeredEvents[$event['id']][$decisionPath][] = $lead->getId();
            }

            if ($log) {
                $logRepo->saveEntity($log);
            }
        } else {
            //else do nothing
            $result = false;
            $this->logger->debug(
                'CAMPAIGN: Timing failed ('.gettype($eventTriggerDate).') for '.ucfirst($event['eventType']).' ID# '.$event['id'].' for contact ID# '
                .$lead->getId()
            );
        }

        if (!empty($log)) {
            // Detach log
            $this->em->detach($log);
            unset($log);
        }

        unset($eventTriggerDate, $event);

        return $result;
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param Campaign $campaign
     * @param int      $evaluatedEventCount
     * @param int      $executedEventCount
     * @param int      $totalEventCount
     */
    public function triggerConditions(Campaign $campaign, &$evaluatedEventCount = 0, &$executedEventCount = 0, &$totalEventCount = 0)
    {
        $eventSettings   = $this->campaignModel->getEvents();
        $repo            = $this->getRepository();
        $sleepBatchCount = 0;
        $limit           = 100;

        while (!empty($this->triggeredEvents)) {
            // Reset the triggered events in order to be hydrated again for chained conditions/actions
            $triggeredEvents       = $this->triggeredEvents;
            $this->triggeredEvents = [];

            foreach ($triggeredEvents as $parentId => $decisionPaths) {
                foreach ($decisionPaths as $decisionPath => $contactIds) {
                    $typeRestriction = null;
                    if ('null' === $decisionPath) {
                        // This is an action so check if conditions are attached
                        $decisionPath    = null;
                        $typeRestriction = 'condition';
                    } // otherwise this should be a condition so get all children connected to the given path

                    $childEvents = $repo->getEventsByParent($parentId, $decisionPath, $typeRestriction);
                    $this->logger->debug(
                        'CAMPAIGN: Evaluating '.count($childEvents).' child event(s) to process the conditions of parent ID# '.$parentId.'.'
                    );

                    if (!count($childEvents)) {
                        continue;
                    }

                    $batchedContactIds = array_chunk($contactIds, $limit);
                    foreach ($batchedContactIds as $batchDebugCounter => $contactBatch) {
                        ++$batchDebugCounter; // start with 1

                        if (empty($contactBatch)) {
                            break;
                        }

                        $this->logger->debug('CAMPAIGN: Batch #'.$batchDebugCounter);

                        $leads = $this->leadModel->getEntities(
                            [
                                'filter'             => [
                                    'force' => [
                                        [
                                            'column' => 'l.id',
                                            'expr'   => 'in',
                                            'value'  => $contactBatch,
                                        ],
                                    ],
                                ],
                                'orderBy'            => 'l.id',
                                'orderByDir'         => 'asc',
                                'withPrimaryCompany' => true,
                            ]
                        );

                        $this->logger->debug('CAMPAIGN: Processing the following contacts: '.implode(', ', array_keys($leads)));

                        if (!count($leads)) {
                            // Just a precaution in case non-existent leads are lingering in the campaign leads table
                            $this->logger->debug('CAMPAIGN: No contact entities found.');

                            continue;
                        }

                        /** @var \Mautic\LeadBundle\Entity\Lead $lead */
                        $leadDebugCounter = 0;
                        foreach ($leads as $lead) {
                            ++$leadDebugCounter; // start with 1

                            $this->logger->debug(
                                'CAMPAIGN: Current Lead ID# '.$lead->getId().'; #'.$leadDebugCounter.' in batch #'.$batchDebugCounter
                            );

                            // Set lead in case this is triggered by the system
                            $this->leadModel->setSystemCurrentLead($lead);

                            foreach ($childEvents as $childEvent) {
                                $this->executeEvent(
                                    $childEvent,
                                    $campaign,
                                    $lead,
                                    $eventSettings,
                                    true,
                                    null,
                                    null,
                                    false,
                                    $evaluatedEventCount,
                                    $executedEventCount,
                                    $totalEventCount
                                );

                                if ($sleepBatchCount == $limit) {
                                    // Keep CPU down
                                    $this->batchSleep();
                                    $sleepBatchCount = 0;
                                } else {
                                    ++$sleepBatchCount;
                                }
                            }
                        }

                        // Free RAM
                        $this->em->clear('Mautic\LeadBundle\Entity\Lead');
                        $this->em->clear('Mautic\UserBundle\Entity\User');
                        unset($leads);

                        // Free some memory
                        gc_collect_cycles();
                    }
                }
            }
        }
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param LeadEventLog $log
     * @param              $status
     * @param null         $reason
     */
    public function setEventStatus(LeadEventLog $log, $status, $reason = null)
    {
        $failedLog = $log->getFailedLog();

        if ($status) {
            if ($failedLog) {
                $this->em->getRepository('MauticCampaignBundle:FailedLeadEventLog')->deleteEntity($failedLog);
                $log->setFailedLog(null);
            }

            $metadata = $log->getMetadata();
            unset($metadata['errors']);
            $log->setMetadata($metadata);
        } else {
            if (!$failedLog) {
                $failedLog = new FailedLeadEventLog();
            }

            $failedLog->setDateAdded()
                ->setReason($reason)
                ->setLog($log);

            $this->em->persist($failedLog);
        }
    }

    /**
     * Check to see if the interval between events are appropriate to fire currentEvent.
     *
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param           $action
     * @param \DateTime $parentTriggeredDate
     * @param bool      $allowNegative
     *
     * @return bool
     */
    public function checkEventTiming($action, \DateTime $parentTriggeredDate = null, $allowNegative = false)
    {
        $now = new \DateTime();

        $this->logger->debug('CAMPAIGN: Check timing for '.ucfirst($action['eventType']).' ID# '.$action['id']);

        if ($action instanceof Event) {
            $action = $action->convertToArray();
        }

        if ($action['decisionPath'] == 'no' && !$allowNegative) {
            $this->logger->debug('CAMPAIGN: '.ucfirst($action['eventType']).' is attached to a negative path which is not allowed');

            return false;
        } else {
            $negate = ($action['decisionPath'] == 'no' && $allowNegative);

            if ($action['triggerMode'] == 'interval') {
                $triggerOn = $negate ? clone $parentTriggeredDate : new \DateTime();

                if ($triggerOn == null) {
                    $triggerOn = new \DateTime();
                }

                $interval = $action['triggerInterval'];
                $unit     = $action['triggerIntervalUnit'];

                $this->logger->debug('CAMPAIGN: Adding interval of '.$interval.$unit.' to '.$triggerOn->format('Y-m-d H:i:s T'));

                $triggerOn->add((new DateTimeHelper())->buildInterval($interval, $unit));

                if ($triggerOn > $now) {
                    $this->logger->debug(
                        'CAMPAIGN: Date to execute ('.$triggerOn->format('Y-m-d H:i:s T').') is later than now ('.$now->format('Y-m-d H:i:s T')
                        .')'.(($action['decisionPath'] == 'no') ? ' so ignore' : ' so schedule')
                    );

                    // Save some RAM for batch processing
                    unset($now, $action, $dv, $dt);

                    //the event is to be scheduled based on the time interval
                    return $triggerOn;
                }
            } elseif ($action['triggerMode'] == 'date') {
                if (!$action['triggerDate'] instanceof \DateTime) {
                    $triggerDate           = new DateTimeHelper($action['triggerDate']);
                    $action['triggerDate'] = $triggerDate->getDateTime();
                    unset($triggerDate);
                }

                $this->logger->debug('CAMPAIGN: Date execution on '.$action['triggerDate']->format('Y-m-d H:i:s T'));

                $pastDue = $now >= $action['triggerDate'];

                if ($negate) {
                    $this->logger->debug(
                        'CAMPAIGN: Negative comparison; Date to execute ('.$action['triggerDate']->format('Y-m-d H:i:s T').') compared to now ('
                        .$now->format('Y-m-d H:i:s T').') and is thus '.(($pastDue) ? 'overdue' : 'not past due')
                    );

                    //it is past the scheduled trigger date and the lead has done nothing so return true to trigger
                    //the event otherwise false to do nothing
                    $return = ($pastDue) ? true : $action['triggerDate'];

                    // Save some RAM for batch processing
                    unset($now, $action);

                    return $return;
                } elseif (!$pastDue) {
                    $this->logger->debug(
                        'CAMPAIGN: Non-negative comparison; Date to execute ('.$action['triggerDate']->format('Y-m-d H:i:s T').') compared to now ('
                        .$now->format('Y-m-d H:i:s T').') and is thus not past due'
                    );

                    //schedule the event
                    return $action['triggerDate'];
                }
            }
        }

        $this->logger->debug('CAMPAIGN: Nothing stopped execution based on timing.');

        //default is to trigger the event
        return true;
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0
     *
     * @param Event|int                                $event
     * @param Campaign                                 $campaign
     * @param \Mautic\LeadBundle\Entity\Lead|null      $lead
     * @param \Mautic\CoreBundle\Entity\IpAddress|null $ipAddress
     * @param bool                                     $systemTriggered
     *
     * @return LeadEventLog
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function getLogEntity($event, $campaign, $lead = null, $ipAddress = null, $systemTriggered = false)
    {
        $log = new LeadEventLog();

        if ($ipAddress == null) {
            // Lead triggered from system IP
            $ipAddress = $this->ipLookupHelper->getIpAddress();
        }
        $log->setIpAddress($ipAddress);

        if (!$event instanceof Event) {
            $event = $this->em->getReference('MauticCampaignBundle:Event', $event);
        }
        $log->setEvent($event);

        if (!$campaign instanceof Campaign) {
            $campaign = $this->em->getReference('MauticCampaignBundle:Campaign', $campaign);
        }
        $log->setCampaign($campaign);

        if ($lead == null) {
            $lead = $this->leadModel->getCurrentLead();
        }
        $log->setLead($lead);
        $log->setDateTriggered(new \DateTime());
        $log->setSystemTriggered($systemTriggered);

        // Save some RAM for batch processing
        unset($event, $campaign, $lead);

        return $log;
    }

    /**
     * Batch sleep according to settings.
     */
    protected function batchSleep()
    {
        // No longer used
    }
}
