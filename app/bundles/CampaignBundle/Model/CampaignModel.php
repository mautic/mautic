<?php

namespace Mautic\CampaignBundle\Model;

use Doctrine\ORM\PersistentCollection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Event as Events;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Form\Type\CampaignType;
use Mautic\CampaignBundle\Helper\ChannelExtractor;
use Mautic\CampaignBundle\Membership\MembershipBuilder;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class CampaignModel extends CommonFormModel
{
    /**
     * @var ListModel
     */
    protected $leadListModel;

    /**
     * @var FormModel
     */
    protected $formModel;

    /**
     * @var EventCollector
     */
    private $eventCollector;

    /**
     * @var MembershipBuilder
     */
    private $membershipBuilder;

    /**
     * @var ContactTracker
     */
    private $contactTracker;

    public function __construct(
        ListModel $leadListModel,
        FormModel $formModel,
        EventCollector $eventCollector,
        MembershipBuilder $membershipBuilder,
        ContactTracker $contactTracker
    ) {
        $this->leadListModel     = $leadListModel;
        $this->formModel         = $formModel;
        $this->eventCollector    = $eventCollector;
        $this->membershipBuilder = $membershipBuilder;
        $this->contactTracker    = $contactTracker;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\CampaignBundle\Entity\CampaignRepository
     */
    public function getRepository()
    {
        $repo = $this->em->getRepository('MauticCampaignBundle:Campaign');
        $repo->setCurrentUser($this->userHelper->getUser());

        return $repo;
    }

    /**
     * @return \Mautic\CampaignBundle\Entity\EventRepository
     */
    public function getEventRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:Event');
    }

    /**
     * @return \Mautic\CampaignBundle\Entity\LeadRepository
     */
    public function getCampaignLeadRepository()
    {
        return $this->em->getRepository('MauticCampaignBundle:Lead');
    }

    /**
     * @return \Mautic\CampaignBundle\Entity\LeadEventLogRepository
     */
    public function getCampaignLeadEventLogRepository()
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
     * {@inheritdoc}
     *
     * @param object      $entity
     * @param object      $formFactory
     * @param string|null $action
     * @param array       $options
     *
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Campaign) {
            throw new MethodNotAllowedHttpException(['Campaign']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(CampaignType::class, $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return Campaign|null
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new Campaign();
        }

        return parent::getEntity($id);
    }

    /**
     * @param object $entity
     */
    public function deleteEntity($entity)
    {
        // Null all the event parents for this campaign to avoid database constraints
        $this->getEventRepository()->nullEventParents($entity->getId());

        parent::deleteEntity($entity);
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, \Symfony\Component\EventDispatcher\Event $event = null)
    {
        if ($entity instanceof \Mautic\CampaignBundle\Entity\Lead) {
            return;
        }

        if (!$entity instanceof Campaign) {
            throw new MethodNotAllowedHttpException(['Campaign']);
        }

        switch ($action) {
            case 'pre_save':
                $name = CampaignEvents::CAMPAIGN_PRE_SAVE;
                break;
            case 'post_save':
                $name = CampaignEvents::CAMPAIGN_POST_SAVE;
                break;
            case 'pre_delete':
                $name = CampaignEvents::CAMPAIGN_PRE_DELETE;
                break;
            case 'post_delete':
                $name = CampaignEvents::CAMPAIGN_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new Events\CampaignEvent($entity, $isNew);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param $sessionEvents
     * @param $sessionConnections
     * @param $deletedEvents
     *
     * @return array
     */
    public function setEvents(Campaign $entity, $sessionEvents, $sessionConnections, $deletedEvents)
    {
        $existingEvents = $entity->getEvents()->toArray();
        $events         = [];
        $hierarchy      = [];

        foreach ($sessionEvents as $properties) {
            $isNew = (!empty($properties['id']) && isset($existingEvents[$properties['id']])) ? false : true;
            $event = !$isNew ? $existingEvents[$properties['id']] : new Event();

            foreach ($properties as $f => $v) {
                if ('id' == $f && 0 === strpos($v, 'new')) {
                    //set the temp ID used to be able to match up connections
                    $event->setTempId($v);
                }

                if (in_array($f, ['id', 'parent'])) {
                    continue;
                }

                $func = 'set'.ucfirst($f);
                if (method_exists($event, $func)) {
                    $event->$func($v);
                }
            }

            ChannelExtractor::setChannel($event, $event, $this->eventCollector->getEventConfig($event));

            $event->setCampaign($entity);
            $events[$properties['id']] = $event;
        }

        foreach ($deletedEvents as $deleteMe) {
            if (isset($existingEvents[$deleteMe])) {
                // Remove child from parent
                $parent = $existingEvents[$deleteMe]->getParent();
                if ($parent) {
                    $parent->removeChild($existingEvents[$deleteMe]);
                    $existingEvents[$deleteMe]->removeParent();
                }

                $entity->removeEvent($existingEvents[$deleteMe]);

                unset($events[$deleteMe]);
            }
        }

        $relationships = [];

        if (isset($sessionConnections['connections'])) {
            foreach ($sessionConnections['connections'] as $connection) {
                $source = $connection['sourceId'];
                $target = $connection['targetId'];

                if (in_array($source, ['lists', 'forms'])) {
                    // Only concerned with events and not sources
                    continue;
                }

                if (isset($connection['anchors']['source'])) {
                    $sourceDecision = $connection['anchors']['source'];
                } else {
                    $sourceDecision = (!empty($connection['anchors'][0])) ? $connection['anchors'][0]['endpoint'] : null;
                }

                if ('leadsource' == $sourceDecision) {
                    // Lead source connection that does not matter
                    continue;
                }

                $relationships[$target] = [
                    'parent'   => $source,
                    'decision' => $sourceDecision,
                ];
            }
        }

        // Assign parent/child relationships
        foreach ($events as $id => $e) {
            if (isset($relationships[$id])) {
                // Has a parent
                $anchor = in_array($relationships[$id]['decision'], ['yes', 'no']) ? $relationships[$id]['decision'] : null;
                $events[$id]->setDecisionPath($anchor);

                $parentId = $relationships[$id]['parent'];
                $events[$id]->setParent($events[$parentId]);

                $hierarchy[$id] = $parentId;
            } elseif ($events[$id]->getParent()) {
                // No longer has a parent so null it out

                // Remove decision so that it doesn't affect execution
                $events[$id]->setDecisionPath(null);

                // Remove child from parent
                $parent = $events[$id]->getParent();
                $parent->removeChild($events[$id]);

                // Remove parent from child
                $events[$id]->removeParent();
                $hierarchy[$id] = 'null';
            } else {
                // Is a parent
                $hierarchy[$id] = 'null';

                // Remove decision so that it doesn't affect execution
                $events[$id]->setDecisionPath(null);
            }
        }

        $entity->addEvents($events);

        //set event order used when querying the events
        $this->buildOrder($hierarchy, $events, $entity);

        uasort(
            $events,
            function ($a, $b) {
                $aOrder = $a->getOrder();
                $bOrder = $b->getOrder();
                if ($aOrder == $bOrder) {
                    return 0;
                }

                return ($aOrder < $bOrder) ? -1 : 1;
            }
        );

        // Persist events if campaign is being edited
        if ($entity->getId()) {
            $this->getEventRepository()->saveEntities($events);
        }

        return $events;
    }

    /**
     * @param      $entity
     * @param      $settings
     * @param bool $persist
     * @param null $events
     *
     * @return array
     */
    public function setCanvasSettings($entity, $settings, $persist = true, $events = null)
    {
        if (null === $events) {
            $events = $entity->getEvents();
        }

        $tempIds = [];

        foreach ($events as $e) {
            if ($e instanceof Event) {
                $tempIds[$e->getTempId()] = $e->getId();
            } else {
                $tempIds[$e['tempId']] = $e['id'];
            }
        }

        if (!isset($settings['nodes'])) {
            $settings['nodes'] = [];
        }

        foreach ($settings['nodes'] as &$node) {
            if (false !== strpos($node['id'], 'new')) {
                // Find the real one and update the node
                $node['id'] = str_replace($node['id'], $tempIds[$node['id']], $node['id']);
            }
        }

        if (!isset($settings['connections'])) {
            $settings['connections'] = [];
        }

        foreach ($settings['connections'] as &$connection) {
            // Check source
            if (false !== strpos($connection['sourceId'], 'new')) {
                // Find the real one and update the node
                $connection['sourceId'] = str_replace($connection['sourceId'], $tempIds[$connection['sourceId']], $connection['sourceId']);
            }

            // Check target
            if (false !== strpos($connection['targetId'], 'new')) {
                // Find the real one and update the node
                $connection['targetId'] = str_replace($connection['targetId'], $tempIds[$connection['targetId']], $connection['targetId']);
            }

            // Rebuild anchors
            if (!isset($connection['anchors']['source'])) {
                $anchors = [];
                foreach ($connection['anchors'] as $k => $anchor) {
                    $type           = (0 === $k) ? 'source' : 'target';
                    $anchors[$type] = $anchor['endpoint'];
                }

                $connection['anchors'] = $anchors;
            }
        }

        $entity->setCanvasSettings($settings);

        if ($persist) {
            $this->getRepository()->saveEntity($entity);
        }

        return $settings;
    }

    /**
     * Get list of sources for a campaign.
     *
     * @param $campaign
     *
     * @return array
     */
    public function getLeadSources($campaign)
    {
        $campaignId = ($campaign instanceof Campaign) ? $campaign->getId() : $campaign;

        $sources = [];

        // Lead lists
        $sources['lists'] = $this->getRepository()->getCampaignListSources($campaignId);

        // Forms
        $sources['forms'] = $this->getRepository()->getCampaignFormSources($campaignId);

        return $sources;
    }

    /**
     * Add and/or delete lead sources from a campaign.
     *
     * @param $entity
     * @param $addedSources
     * @param $deletedSources
     */
    public function setLeadSources(Campaign $entity, $addedSources, $deletedSources)
    {
        foreach ($addedSources as $type => $sources) {
            foreach ($sources as $id => $label) {
                switch ($type) {
                    case 'lists':
                        $entity->addList($this->em->getReference('MauticLeadBundle:LeadList', $id));
                        break;
                    case 'forms':
                        $entity->addForm($this->em->getReference('MauticFormBundle:Form', $id));
                        break;
                    default:
                        break;
                }
            }
        }

        foreach ($deletedSources as $type => $sources) {
            foreach ($sources as $id => $label) {
                switch ($type) {
                    case 'lists':
                        $entity->removeList($this->em->getReference('MauticLeadBundle:LeadList', $id));
                        break;
                    case 'forms':
                        $entity->removeForm($this->em->getReference('MauticFormBundle:Form', $id));
                        break;
                    default:
                        break;
                }
            }
        }
    }

    /**
     * Get a list of source choices.
     *
     * @param string $sourceType
     * @param bool   $globalOnly
     *
     * @return array
     */
    public function getSourceLists($sourceType = null, $globalOnly = false)
    {
        $choices = [];
        switch ($sourceType) {
            case 'lists':
            case null:
                $choices['lists'] = [];
                $lists            = $globalOnly ? $this->leadListModel->getGlobalLists() : $this->leadListModel->getUserLists();

                if ($lists) {
                    foreach ($lists as $list) {
                        $choices['lists'][$list['id']] = $list['name'];
                    }
                }

            // no break
            case 'forms':
            case null:
                $choices['forms'] = [];
                $viewOther        = $this->security->isGranted('form:forms:viewother');
                $repo             = $this->formModel->getRepository();
                $repo->setCurrentUser($this->userHelper->getUser());

                $forms = $repo->getFormList('', 0, 0, $viewOther, 'campaign');

                if ($forms) {
                    foreach ($forms as $form) {
                        $choices['forms'][$form['id']] = $form['name'];
                    }
                }
        }

        foreach ($choices as &$typeChoices) {
            asort($typeChoices);
        }

        return (null == $sourceType) ? $choices : $choices[$sourceType];
    }

    /**
     * @param mixed $form
     *
     * @return array
     */
    public function getCampaignsByForm($form)
    {
        $formId = ($form instanceof Form) ? $form->getId() : $form;

        return $this->getRepository()->findByFormId($formId);
    }

    /**
     * Gets the campaigns a specific lead is part of.
     *
     * @param Lead $lead
     * @param bool $forList
     *
     * @return mixed
     */
    public function getLeadCampaigns(Lead $lead = null, $forList = false)
    {
        static $campaigns = [];

        if (null === $lead) {
            $lead = $this->contactTracker->getContact();
        }

        if (!isset($campaigns[$lead->getId()])) {
            $repo   = $this->getRepository();
            $leadId = $lead->getId();
            //get the campaigns the lead is currently part of
            $campaigns[$leadId] = $repo->getPublishedCampaigns(
                null,
                $lead->getId(),
                $forList,
                $this->security->isGranted($this->getPermissionBase().':viewother')
            );
        }

        return $campaigns[$lead->getId()];
    }

    /**
     * Gets a list of published campaigns.
     *
     * @param bool $forList
     *
     * @return array
     */
    public function getPublishedCampaigns($forList = false)
    {
        static $campaigns = [];

        if (empty($campaigns)) {
            $campaigns = $this->getRepository()->getPublishedCampaigns(
                null,
                null,
                $forList,
                $this->security->isGranted($this->getPermissionBase().':viewother')
            );
        }

        return $campaigns;
    }

    /**
     * Saves a campaign lead, logs the error if saving fails.
     *
     * @return bool
     */
    public function saveCampaignLead(CampaignLead $campaignLead)
    {
        try {
            $this->getCampaignLeadRepository()->saveEntity($campaignLead);

            return true;
        } catch (\Exception $exception) {
            $this->logger->log('error', $exception->getMessage(), ['exception' => $exception]);

            return false;
        }
    }

    /**
     * Get details of leads in a campaign.
     *
     * @param      $campaign
     * @param null $leads
     *
     * @return mixed
     */
    public function getLeadDetails($campaign, $leads = null)
    {
        $campaignId = ($campaign instanceof Campaign) ? $campaign->getId() : $campaign;

        if ($leads instanceof PersistentCollection) {
            $leads = array_keys($leads->toArray());
        }

        return $this->em->getRepository('MauticCampaignBundle:Lead')->getLeadDetails($campaignId, $leads);
    }

    /**
     * Get leads for a campaign.  If $event is passed in, only leads who have not triggered the event are returned.
     *
     * @param Campaign $campaign
     * @param array    $event
     *
     * @return mixed
     */
    public function getCampaignLeads($campaign, $event = null)
    {
        $campaignId = ($campaign instanceof Campaign) ? $campaign->getId() : $campaign;
        $eventId    = (is_array($event) && isset($event['id'])) ? $event['id'] : $event;

        return $this->em->getRepository('MauticCampaignBundle:Lead')->getLeads($campaignId, $eventId);
    }

    /**
     * @param $id
     *
     * @return array
     */
    public function getCampaignListIds($id)
    {
        return $this->getRepository()->getCampaignListIds((int) $id);
    }

    /**
     * Get line chart data of leads added to campaigns.
     *
     * @param string $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string $dateFormat
     * @param array  $filter
     * @param bool   $canViewOthers
     *
     * @return array
     */
    public function getLeadsAddedLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true)
    {
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $q     = $query->prepareTimeDataQuery('campaign_leads', 'date_added', $filter);

        if (!$canViewOthers) {
            $q->join('t', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'c.id = c.campaign_id')
                ->andWhere('c.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $data = $query->loadAndBuildTimeData($q);
        $chart->setDataset($this->translator->trans('mautic.campaign.campaign.leads'), $data);

        return $chart->render();
    }

    /**
     * Get line chart data of hits.
     *
     * @param string|null $unit       {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string      $dateFormat
     * @param array       $filter
     *
     * @return array
     */
    public function getCampaignMetricsLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [])
    {
        $events = [];
        $chart  = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query  = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        $contacts = $query->fetchTimeData('campaign_leads', 'date_added', $filter);
        $chart->setDataset($this->translator->trans('mautic.campaign.campaign.leads'), $contacts);

        if (isset($filter['campaign_id'])) {
            $rawEvents = $this->getEventRepository()->getCampaignEvents($filter['campaign_id']);

            // Group events by type
            if ($rawEvents) {
                foreach ($rawEvents as $event) {
                    if (isset($events[$event['type']])) {
                        $events[$event['type']][] = $event['id'];
                    } else {
                        $events[$event['type']] = [$event['id']];
                    }
                }
            }

            if ($events) {
                foreach ($events as $type => $eventIds) {
                    $filter['event_id'] = $eventIds;

                    if ($this->coreParametersHelper->get('campaign_use_summary')) {
                        $q       = $query->prepareTimeDataQuery('campaign_summary', 'date_triggered', $filter, 'triggered_count + non_action_path_taken_count', 'sum');
                        $rawData = $q->execute()->fetchAll();
                    } else {
                        // Exclude failed events
                        $failedSq = $this->em->getConnection()->createQueryBuilder();
                        $failedSq->select('null')
                            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_failed_log', 'fe')
                            ->where(
                                $failedSq->expr()->eq('fe.log_id', 't.id')
                            );
                        $filter['failed_events'] = [
                            'subquery' => sprintf('NOT EXISTS (%s)', $failedSq->getSQL()),
                        ];

                        $q       = $query->prepareTimeDataQuery('campaign_lead_event_log', 'date_triggered', $filter);
                        $rawData = $q->execute()->fetchAll();
                    }

                    if (!empty($rawData)) {
                        $triggers = $query->completeTimeData($rawData);
                        $chart->setDataset($this->translator->trans('mautic.campaign.'.$type), $triggers);
                    }
                }
                unset($filter['event_id']);
            }
        }

        return $chart->render();
    }

    /**
     * @param          $hierarchy
     * @param Campaign $entity
     * @param string   $root
     * @param int      $order
     */
    protected function buildOrder($hierarchy, &$events, $entity, $root = 'null', $order = 1)
    {
        $count = count($hierarchy);
        if (1 === $count && 'null' === array_unique(array_values($hierarchy))[0]) {
            // no parents so leave order as is

            return;
        } else {
            foreach ($hierarchy as $eventId => $parent) {
                if ($parent == $root || 1 === $count) {
                    $events[$eventId]->setOrder($order);
                    unset($hierarchy[$eventId]);
                    if (count($hierarchy)) {
                        $this->buildOrder($hierarchy, $events, $entity, $eventId, $order + 1);
                    }
                }
            }
        }
    }

    /**
     * @param int             $limit
     * @param bool            $maxLeads
     * @param OutputInterface $output
     *
     * @return int
     */
    public function rebuildCampaignLeads(Campaign $campaign, $limit = 1000, $maxLeads = false, OutputInterface $output = null)
    {
        $contactLimiter = new ContactLimiter($limit);

        return $this->membershipBuilder->build($campaign, $contactLimiter, $maxLeads, $output);
    }

    /**
     * @param $segmentId
     *
     * @return array
     */
    public function getCampaignIdsWithDependenciesOnSegment($segmentId)
    {
        $entities = $this->getRepository()->getEntities(
            [
                'filter'    => [
                    'force' => [
                        [
                            'column' => 'l.id',
                            'expr'   => 'eq',
                            'value'  => $segmentId,
                        ],
                    ],
                ],
                'joinLists' => true,
            ]
        );

        $ids = [];
        foreach ($entities as $entity) {
            $ids[] = $entity->getId();
        }

        return $ids;
    }
}
