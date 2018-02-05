<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Model;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Executioner\DecisionExecutioner;
use Mautic\CampaignBundle\Executioner\KickoffExecutioner;
use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EventModel
 * {@inheritdoc}
 */
class EventModel extends LegacyEventModel
{
    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * @var UserModel
     */
    protected $userModel;

    /**
     * @var NotificationModel
     */
    protected $notificationModel;

    /**
     * EventModel constructor.
     *
     * @param IpLookupHelper       $ipLookupHelper
     * @param LeadModel            $leadModel
     * @param CampaignModel        $campaignModel
     * @param UserModel            $userModel
     * @param NotificationModel    $notificationModel
     * @param DecisionExecutioner  $decisionExecutioner
     * @param KickoffExecutioner   $kickoffExecutioner
     * @param ScheduledExecutioner $scheduledExecutioner
     */
    public function __construct(
        IpLookupHelper $ipLookupHelper,
        LeadModel $leadModel,
        CampaignModel $campaignModel,
        UserModel $userModel,
        NotificationModel $notificationModel,
        DecisionExecutioner $decisionExecutioner,
        KickoffExecutioner $kickoffExecutioner,
        ScheduledExecutioner $scheduledExecutioner
    ) {
        $this->ipLookupHelper       = $ipLookupHelper;
        $this->leadModel            = $leadModel;
        $this->campaignModel        = $campaignModel;
        $this->userModel            = $userModel;
        $this->notificationModel    = $notificationModel;

        parent::__construct($decisionExecutioner, $kickoffExecutioner, $scheduledExecutioner);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\CampaignBundle\Entity\EventRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:Event');
    }

    /**
     * Get CampaignRepository.
     *
     * @return \Mautic\CampaignBundle\Entity\CampaignRepository
     */
    public function getCampaignRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:Campaign');
    }

    /**
     * @return LeadEventLogRepository
     */
    public function getLeadEventLogRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:LeadEventLog');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'campaign:campaigns';
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Event();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }

    /**
     * Delete events.
     *
     * @param $currentEvents
     * @param $deletedEvents
     */
    public function deleteEvents($currentEvents, $deletedEvents)
    {
        $deletedKeys = [];
        foreach ($deletedEvents as $k => $deleteMe) {
            if ($deleteMe instanceof Event) {
                $deleteMe = $deleteMe->getId();
            }

            if (strpos($deleteMe, 'new') === 0) {
                unset($deletedEvents[$k]);
            }

            if (isset($currentEvents[$deleteMe])) {
                unset($deletedEvents[$k]);
            }

            if (isset($deletedEvents[$k])) {
                $deletedKeys[] = $deleteMe;
            }
        }

        if (count($deletedEvents)) {
            // wipe out any references to these events to prevent restraint violations
            $this->getRepository()->nullEventRelationships($deletedKeys);

            // delete the events
            $this->deleteEntities($deletedEvents);
        }
    }

    /**
     * Find and trigger the negative events, i.e. the events with a no decision path.
     *
     * @param Campaign        $campaign
     * @param int             $totalEventCount
     * @param int             $limit
     * @param bool            $max
     * @param OutputInterface $output
     * @param bool|false      $returnCounts    If true, returns array of counters
     *
     * @return int
     */
    public function triggerNegativeEvents(
        $campaign,
        &$totalEventCount = 0,
        $limit = 100,
        $max = false,
        OutputInterface $output = null,
        $returnCounts = false
    ) {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $this->logger->debug('CAMPAIGN: Triggering negative events');

        $campaignId   = $campaign->getId();
        $repo         = $this->getRepository();
        $campaignRepo = $this->getCampaignRepository();
        $logRepo      = $this->getLeadEventLogRepository();

        // Get events to avoid large number of joins
        $campaignEvents = $repo->getCampaignEvents($campaignId);

        // Get an array of events that are non-action based
        $nonActionEvents = [];
        $actionEvents    = [];
        foreach ($campaignEvents as $id => $e) {
            if (!empty($e['decisionPath']) && !empty($e['parent_id']) && $campaignEvents[$e['parent_id']]['eventType'] != 'condition') {
                if ($e['decisionPath'] == 'no') {
                    $nonActionEvents[$e['parent_id']][$id] = $e;
                } elseif ($e['decisionPath'] == 'yes') {
                    $actionEvents[$e['parent_id']][] = $id;
                }
            }
        }

        $this->logger->debug('CAMPAIGN: Processing the children of the following events: '.implode(', ', array_keys($nonActionEvents)));

        if (empty($nonActionEvents)) {
            // No non-action events associated with this campaign
            unset($campaignEvents);

            return 0;
        }

        // Get a count
        $leadCount = $campaignRepo->getCampaignLeadCount($campaignId);

        if ($output) {
            $output->writeln(
                $this->translator->trans(
                    'mautic.campaign.trigger.lead_count_analyzed',
                    ['%leads%' => $leadCount, '%batch%' => $limit]
                )
            );
        }

        $start               = $leadProcessedCount               = $lastRoundPercentage               = $executedEventCount               = $evaluatedEventCount               = $negativeExecutedCount               = $negativeEvaluatedCount               = 0;
        $nonActionEventCount = $leadCount * count($nonActionEvents);
        $eventSettings       = $this->campaignModel->getEvents();
        $maxCount            = ($max) ? $max : $nonActionEventCount;

        // Try to save some memory
        gc_enable();

        if ($leadCount) {
            if ($output) {
                $progress = ProgressBarHelper::init($output, $maxCount);
                $progress->start();
                if ($max) {
                    $progress->advance($totalEventCount);
                }
            }

            $sleepBatchCount   = 0;
            $batchDebugCounter = 1;
            while ($start <= $leadCount) {
                $this->logger->debug('CAMPAIGN: Batch #'.$batchDebugCounter);

                // Get batched campaign ids
                $campaignLeads = $campaignRepo->getCampaignLeads($campaignId, $start, $limit, ['cl.lead_id, cl.date_added']);

                $campaignLeadIds   = [];
                $campaignLeadDates = [];
                foreach ($campaignLeads as $r) {
                    $campaignLeadIds[]                = $r['lead_id'];
                    $campaignLeadDates[$r['lead_id']] = $r['date_added'];
                }

                unset($campaignLeads);

                $this->logger->debug('CAMPAIGN: Processing the following contacts: '.implode(', ', $campaignLeadIds));

                foreach ($nonActionEvents as $parentId => $events) {
                    // Just a check to ensure this is an appropriate action
                    if ($campaignEvents[$parentId]['eventType'] == 'action') {
                        $this->logger->debug('CAMPAIGN: Parent event ID #'.$parentId.' is an action.');

                        continue;
                    }

                    // Get only leads who have had the action prior to the decision executed
                    $grandParentId = $campaignEvents[$parentId]['parent_id'];

                    // Get the lead log for this batch of leads limiting to those that have already triggered
                    // the decision's parent and haven't executed this level in the path yet
                    if ($grandParentId) {
                        $this->logger->debug('CAMPAIGN: Checking for contacts based on grand parent execution.');

                        $leadLog         = $repo->getEventLog($campaignId, $campaignLeadIds, [$grandParentId], array_keys($events), true);
                        $applicableLeads = array_keys($leadLog);
                    } else {
                        $this->logger->debug('CAMPAIGN: Checking for contacts based on exclusion due to being at root level');

                        // The event has no grandparent (likely because the decision is first in the campaign) so find leads that HAVE
                        // already executed the events in the root level and exclude them
                        $havingEvents = (isset($actionEvents[$parentId]))
                            ? array_merge($actionEvents[$parentId], array_keys($events))
                            : array_keys(
                                $events
                            );
                        $leadLog           = $repo->getEventLog($campaignId, $campaignLeadIds, $havingEvents);
                        $unapplicableLeads = array_keys($leadLog);

                        // Only use leads that are not applicable
                        $applicableLeads = array_diff($campaignLeadIds, $unapplicableLeads);

                        unset($unapplicableLeads);
                    }

                    if (empty($applicableLeads)) {
                        $this->logger->debug('CAMPAIGN: No events are applicable');

                        continue;
                    }

                    $this->logger->debug('CAMPAIGN: These contacts have have not gone down the positive path: '.implode(', ', $applicableLeads));

                    // Get the leads
                    $leads = $this->leadModel->getEntities(
                        [
                            'filter' => [
                                'force' => [
                                    [
                                        'column' => 'l.id',
                                        'expr'   => 'in',
                                        'value'  => $applicableLeads,
                                    ],
                                ],
                            ],
                            'orderBy'            => 'l.id',
                            'orderByDir'         => 'asc',
                            'withPrimaryCompany' => true,
                            'withChannelRules'   => true,
                        ]
                    );

                    if (!count($leads)) {
                        // Just a precaution in case non-existent leads are lingering in the campaign leads table
                        $this->logger->debug('CAMPAIGN: No contact entities found.');

                        continue;
                    }

                    // Loop over the non-actions and determine if it has been processed for this lead

                    $leadDebugCounter = 1;
                    /** @var \Mautic\LeadBundle\Entity\Lead $lead */
                    foreach ($leads as $lead) {
                        ++$negativeEvaluatedCount;

                        // Set lead for listeners
                        $this->leadModel->setSystemCurrentLead($lead);

                        $this->logger->debug('CAMPAIGN: contact ID #'.$lead->getId().'; #'.$leadDebugCounter.' in batch #'.$batchDebugCounter);

                        // Prevent path if lead has already gone down this path
                        if (!isset($leadLog[$lead->getId()]) || !array_key_exists($parentId, $leadLog[$lead->getId()])) {
                            // Get date to compare against
                            $utcDateString = ($grandParentId) ? $leadLog[$lead->getId()][$grandParentId]['date_triggered']
                                : $campaignLeadDates[$lead->getId()];

                            // Convert to local DateTime
                            $grandParentDate = (new DateTimeHelper($utcDateString))->getLocalDateTime();

                            // Non-decision has not taken place yet, so cycle over each associated action to see if timing is right
                            $eventTiming   = [];
                            $executeAction = false;
                            foreach ($events as $id => $e) {
                                if ($sleepBatchCount == $limit) {
                                    // Keep CPU down
                                    $this->batchSleep();
                                    $sleepBatchCount = 0;
                                } else {
                                    ++$sleepBatchCount;
                                }

                                if (isset($leadLog[$lead->getId()]) && array_key_exists($id, $leadLog[$lead->getId()])) {
                                    $this->logger->debug('CAMPAIGN: Event (ID #'.$id.') has already been executed');
                                    unset($e);

                                    continue;
                                }

                                if (!isset($eventSettings[$e['eventType']][$e['type']])) {
                                    $this->logger->debug('CAMPAIGN: Event (ID #'.$id.') no longer exists');
                                    unset($e);

                                    continue;
                                }

                                // First get the timing for all the 'non-decision' actions
                                $eventTiming[$id] = $this->checkEventTiming($e, $grandParentDate, true);
                                if ($eventTiming[$id] === true) {
                                    // Includes events to be executed now then schedule the rest if applicable
                                    $executeAction = true;
                                }

                                unset($e);
                            }

                            if (!$executeAction) {
                                $negativeEvaluatedCount += count($nonActionEvents);

                                // Timing is not appropriate so move on to next lead
                                unset($eventTiming);

                                continue;
                            }

                            if ($max && ($totalEventCount + count($nonActionEvents)) >= $max) {
                                // Hit the max or will hit the max while mid-process for the lead
                                if ($output && isset($progress)) {
                                    $progress->finish();
                                    $output->writeln('');
                                }

                                $counts = [
                                    'events'         => $nonActionEventCount,
                                    'evaluated'      => $negativeEvaluatedCount,
                                    'executed'       => $negativeExecutedCount,
                                    'totalEvaluated' => $evaluatedEventCount,
                                    'totalExecuted'  => $executedEventCount,
                                ];
                                $this->logger->debug('CAMPAIGN: Counts - '.var_export($counts, true));

                                return ($returnCounts) ? $counts : $executedEventCount;
                            }

                            $decisionLogged = false;

                            // Execute or schedule events
                            $this->logger->debug(
                                'CAMPAIGN: Processing the following events for contact ID# '.$lead->getId().': '.implode(
                                    ', ',
                                    array_keys($eventTiming)
                                )
                            );

                            foreach ($eventTiming as $id => $eventTriggerDate) {
                                // Set event
                                $event             = $events[$id];
                                $event['campaign'] = [
                                    'id'        => $campaign->getId(),
                                    'name'      => $campaign->getName(),
                                    'createdBy' => $campaign->getCreatedBy(),
                                ];

                                // Set lead in case this is triggered by the system
                                $this->leadModel->setSystemCurrentLead($lead);

                                if ($this->executeEvent(
                                    $event,
                                    $campaign,
                                    $lead,
                                    $eventSettings,
                                    false,
                                    null,
                                    $eventTriggerDate,
                                    false,
                                    $evaluatedEventCount,
                                    $executedEventCount,
                                    $totalEventCount
                                )
                                ) {
                                    if (!$decisionLogged) {
                                        // Log the decision
                                        $log = $this->getLogEntity($parentId, $campaign, $lead, null, true);
                                        $log->setDateTriggered(new \DateTime());
                                        $log->setNonActionPathTaken(true);
                                        $logRepo->saveEntity($log);
                                        $this->em->detach($log);
                                        unset($log);

                                        $decisionLogged = true;
                                    }

                                    ++$negativeExecutedCount;
                                }

                                unset($utcDateString, $grandParentDate);
                            }
                        } else {
                            $this->logger->debug('CAMPAIGN: Decision has already been executed.');
                        }

                        $currentCount = ($max) ? $totalEventCount : $negativeEvaluatedCount;
                        if ($output && isset($progress) && $currentCount < $maxCount) {
                            $progress->setProgress($currentCount);
                        }

                        ++$leadDebugCounter;

                        // Save RAM
                        $this->em->detach($lead);
                        unset($lead);
                    }
                }

                // Next batch
                $start += $limit;

                // Save RAM
                $this->em->clear('Mautic\LeadBundle\Entity\Lead');
                $this->em->clear('Mautic\UserBundle\Entity\User');

                unset($leads, $campaignLeadIds, $leadLog);

                $currentCount = ($max) ? $totalEventCount : $negativeEvaluatedCount;
                if ($output && isset($progress) && $currentCount < $maxCount) {
                    $progress->setProgress($currentCount);
                }

                // Free some memory
                gc_collect_cycles();

                ++$batchDebugCounter;
            }

            if ($output && isset($progress)) {
                $progress->finish();
                $output->writeln('');
            }

            $this->triggerConditions($campaign, $evaluatedEventCount, $executedEventCount, $totalEventCount);
        }

        $counts = [
            'events'         => $nonActionEventCount,
            'evaluated'      => $negativeEvaluatedCount,
            'executed'       => $negativeExecutedCount,
            'totalEvaluated' => $evaluatedEventCount,
            'totalExecuted'  => $executedEventCount,
        ];
        $this->logger->debug('CAMPAIGN: Counts - '.var_export($counts, true));

        return ($returnCounts) ? $counts : $executedEventCount;
    }

    /**
     * Get line chart data of campaign events.
     *
     * @param string    $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param string    $dateFormat
     * @param array     $filter
     * @param bool      $canViewOthers
     *
     * @return array
     */
    public function getEventLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true)
    {
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $q     = $query->prepareTimeDataQuery('campaign_lead_event_log', 'date_triggered', $filter);

        if (!$canViewOthers) {
            $q->join('t', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'c.id = c.campaign_id')
                ->andWhere('c.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $data = $query->loadAndBuildTimeData($q);
        $chart->setDataset($this->translator->trans('mautic.campaign.triggered.events'), $data);

        return $chart->render();
    }

    /**
     * @param Lead $lead
     * @param      $campaignCreatedBy
     * @param      $header
     */
    public function notifyOfFailure(Lead $lead, $campaignCreatedBy, $header)
    {
        // Notify the lead owner if there is one otherwise campaign creator that there was a failure
        if (!$owner = $lead->getOwner()) {
            $ownerId = (int) $campaignCreatedBy;
            $owner   = $this->userModel->getEntity($ownerId);
        }

        if ($owner && $owner->getId()) {
            $this->notificationModel->addNotification(
                $header,
                'error',
                false,
                $this->translator->trans(
                    'mautic.campaign.event.failed',
                    [
                        '%contact%' => '<a href="'.$this->router->generate(
                                'mautic_contact_action',
                                ['objectAction' => 'view', 'objectId' => $lead->getId()]
                            ).'" data-toggle="ajax">'.$lead->getPrimaryIdentifier().'</a>',
                    ]
                ),
                null,
                null,
                $owner
            );
        }
    }
}
