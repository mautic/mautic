<?php

namespace Mautic\LeadBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Helper\Chart\BarChart;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\Chart\PieChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Entity\ListLeadRepository;
use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Event\LeadListEvent;
use Mautic\LeadBundle\Event\LeadListFiltersChoicesEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Mautic\LeadBundle\Event\ListPreProcessListEvent;
use Mautic\LeadBundle\Form\Type\ListType;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\ContactSegmentService;
use Mautic\LeadBundle\Segment\Exception\FieldNotFoundException;
use Mautic\LeadBundle\Segment\Exception\SegmentNotFoundException;
use Mautic\LeadBundle\Segment\Stat\ChartQuery\SegmentContactsLineChartQuery;
use Mautic\LeadBundle\Segment\Stat\SegmentChartQueryFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<LeadList>
 */
class ListModel extends FormModel
{
    use OperatorListTrait;

    /**
     * @var mixed[]
     */
    private array $choiceFieldsCache = [];

    public function __construct(
        protected CategoryModel $categoryModel,
        CoreParametersHelper $coreParametersHelper,
        private ContactSegmentService $leadSegmentService,
        private SegmentChartQueryFactory $segmentChartQueryFactory,
        private RequestStack $requestStack,
        private SegmentCountCacheHelper $segmentCountCacheHelper,
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * Used by addLead and removeLead functions.
     */
    private array $leadChangeLists = [];

    /**
     * @return LeadListRepository
     */
    public function getRepository()
    {
        /** @var LeadListRepository $repo */
        $repo = $this->em->getRepository(LeadList::class);

        $repo->setDispatcher($this->dispatcher);
        $repo->setTranslator($this->translator);

        return $repo;
    }

    /**
     * Returns the repository for the table that houses the leads associated with a list.
     *
     * @return ListLeadRepository
     */
    public function getListLeadRepository()
    {
        return $this->em->getRepository(ListLead::class);
    }

    public function getPermissionBase(): string
    {
        return 'lead:lists';
    }

    /**
     * @param bool $unlock
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function saveEntity($entity, $unlock = true): void
    {
        $isNew = ($entity->getId()) ? false : true;

        // set some defaults
        $this->setTimestamps($entity, $isNew, $unlock);

        $alias = $entity->getAlias();
        if (empty($alias)) {
            $alias = $entity->getName();
        }
        $alias = $this->cleanAlias($alias, '', 0, '-');

        // make sure alias is not already taken
        $repo      = $this->getRepository();
        $testAlias = $alias;
        $existing  = $repo->getLists(null, $testAlias, $entity->getId());
        $count     = count($existing);
        $aliasTag  = $count;

        while ($count) {
            $testAlias = $alias.$aliasTag;
            $existing  = $repo->getLists(null, $testAlias, $entity->getId());
            $count     = count($existing);
            ++$aliasTag;
        }
        if ($testAlias != $alias) {
            $alias = $testAlias;
        }
        $entity->setAlias($alias);

        $publicName = $entity->getPublicName();
        if (empty($publicName)) {
            $entity->setPublicName($entity->getName());
        }

        $event = $this->dispatchEvent('pre_save', $entity, $isNew);
        $repo->saveEntity($entity);
        $this->dispatchEvent('post_save', $entity, $isNew, $event);
    }

    /**
     * @param string|null $action
     * @param array       $options
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof LeadList) {
            throw new MethodNotAllowedHttpException(['LeadList'], 'Entity must be of class LeadList()');
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(ListType::class, $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     */
    public function getEntity($id = null): ?LeadList
    {
        if (null === $id) {
            return new LeadList();
        }

        return parent::getEntity($id);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null): ?Event
    {
        if (!$entity instanceof LeadList) {
            throw new MethodNotAllowedHttpException(['LeadList'], 'Entity must be of class LeadList()');
        }

        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::LIST_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::LIST_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::LIST_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeadEvents::LIST_POST_DELETE;
                break;
            case 'pre_unpublish':
                $name = LeadEvents::LIST_PRE_UNPUBLISH;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new LeadListEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($event, $name);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * Get a list of field choices for filters.
     *
     * @return mixed[]
     */
    public function getChoiceFields(string $search = ''): array
    {
        if ($this->choiceFieldsCache) {
            return $this->choiceFieldsCache;
        }

        $choices = [];

        $choices['lead']['tags'] =
            [
                'label'      => $this->translator->trans('mautic.lead.list.filter.tags'),
                'properties' => [
                    'type' => 'tags',
                ],
                'operators'  => $this->getOperatorsForFieldType('multiselect'),
                'object'     => 'lead',
            ];

        // Add custom choices
        if ($this->dispatcher->hasListeners(LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE)) {
            $event = new LeadListFiltersChoicesEvent([], $this->getOperatorsForFieldType(), $this->translator, $this->requestStack->getCurrentRequest(), $search);
            $this->dispatcher->dispatch($event, LeadEvents::LIST_FILTERS_CHOICES_ON_GENERATE);
            $choices = $event->getChoices();
        }

        // Order choices by label.
        foreach ($choices as $key => $choice) {
            $cmp = fn ($a, $b): int => strcmp($a['label'], $b['label']);
            uasort($choice, $cmp);
            $choices[$key] = $choice;
        }

        $this->choiceFieldsCache = $choices;

        return $choices;
    }

    /**
     * @param string $alias
     *
     * @return array
     */
    public function getUserLists($alias = '')
    {
        $user = !$this->security->isGranted('lead:lists:viewother') ? $this->userHelper->getUser() : null;

        return $this->em->getRepository(LeadList::class)->getLists($user, $alias);
    }

    /**
     * Get a list of global lead lists.
     *
     * @return mixed
     */
    public function getGlobalLists()
    {
        return $this->em->getRepository(LeadList::class)->getGlobalLists();
    }

    /**
     * Get a list of preference center lead lists.
     *
     * @return mixed
     */
    public function getPreferenceCenterLists()
    {
        return $this->em->getRepository(LeadList::class)->getPreferenceCenterList();
    }

    /**
     * @param int      $limit
     * @param bool|int $maxLeads
     *
     * @throws \Exception
     */
    public function rebuildListLeads(LeadList $leadList, $limit = 100, $maxLeads = false, OutputInterface $output = null): int
    {
        defined('MAUTIC_REBUILDING_LEAD_LISTS') or define('MAUTIC_REBUILDING_LEAD_LISTS', 1);

        $segmentId = $leadList->getId();

        $dtHelper = new DateTimeHelper();

        $batchLimiters = ['dateTime' => $dtHelper->toUtcString()];
        $list          = ['id' => $segmentId, 'filters' => $leadList->getFilters()];

        $this->dispatcher->dispatch(
            new ListPreProcessListEvent($list, false), LeadEvents::LIST_PRE_PROCESS_LIST
        );

        try {
            // Get a count of leads to add
            $newLeadsCount = $this->leadSegmentService->getNewLeadListLeadsCount($leadList, $batchLimiters);
        } catch (FieldNotFoundException) {
            // A field from filter does not exist anymore. Do not rebuild.
            return 0;
        } catch (SegmentNotFoundException) {
            // A segment from filter does not exist anymore. Do not rebuild.
            return 0;
        }

        // Ensure the same list is used each batch <- would love to know how
        $batchLimiters['maxId'] = (int) $newLeadsCount[$segmentId]['maxId'];

        // Number of total leads to process
        $leadCount = (int) $newLeadsCount[$segmentId]['count'];

        $this->logger->info('Segment QB - No new leads for segment found');

        if ($output) {
            $output->writeln($this->translator->trans('mautic.lead.list.rebuild.to_be_added', ['%leads%' => $leadCount, '%batch%' => $limit]));
        }

        // Handle by batches
        $start = $leadsProcessed = 0;

        // Try to save some memory
        gc_enable();

        if ($leadCount) {
            $maxCount = $maxLeads ?: $leadCount;

            if ($output) {
                $progress = ProgressBarHelper::init($output, $maxCount);
                $progress->start();
            }

            // Add leads
            while ($start < $leadCount) {
                // Keep CPU down for large lists; sleep per $limit batch
                $this->batchSleep();

                $this->logger->debug(sprintf('Segment QB - Fetching new leads for segment [%d] %s', $segmentId, $leadList->getName()));
                $newLeadList = $this->leadSegmentService->getNewLeadListLeads($leadList, $batchLimiters, $limit);

                if (empty($newLeadList[$segmentId])) {
                    // Somehow ran out of leads so break out
                    break;
                }

                $this->logger->debug(sprintf('Segment QB - Adding %d new leads to segment [%d] %s', count($newLeadList[$segmentId]), $segmentId, $leadList->getName()));
                foreach ($newLeadList[$segmentId] as $l) {
                    $this->logger->debug(sprintf('Segment QB - Adding lead #%s to segment [%d] %s', $l['id'], $segmentId, $leadList->getName()));

                    $this->addLead($l, $leadList, false, true, -1, $dtHelper->getLocalDateTime());

                    ++$leadsProcessed;
                    if ($output && $leadsProcessed < $maxCount) {
                        $progress->setProgress($leadsProcessed);
                    }

                    if ($maxLeads && $leadsProcessed >= $maxLeads) {
                        break;
                    }
                }

                $this->logger->info(sprintf('Segment QB - Added %d new leads to segment [%d] %s', count($newLeadList[$segmentId]), $segmentId, $leadList->getName()));

                $start += $limit;

                // Dispatch batch event
                if ($this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_BATCH_CHANGE)) {
                    $this->dispatcher->dispatch(
                        new ListChangeEvent($newLeadList[$segmentId], $leadList, true),
                        LeadEvents::LEAD_LIST_BATCH_CHANGE
                    );
                }

                unset($newLeadList);

                // Free some memory
                gc_collect_cycles();

                if ($maxLeads && $leadsProcessed >= $maxLeads) {
                    if ($output) {
                        $progress->finish();
                        $output->writeln('');
                    }

                    return $leadsProcessed;
                }
            }

            if ($output) {
                $progress->finish();
                $output->writeln('');
            }
        }

        // Unset max ID to prevent capping at newly added max ID
        unset($batchLimiters['maxId']);

        $orphanLeadsCount = $this->leadSegmentService->getOrphanedLeadListLeadsCount($leadList);

        // Ensure the same list is used each batch
        $batchLimiters['maxId'] = (int) $orphanLeadsCount[$segmentId]['maxId'];

        // Restart batching
        $start     = 0;
        $leadCount = $orphanLeadsCount[$segmentId]['count'];

        if ($output) {
            $output->writeln($this->translator->trans('mautic.lead.list.rebuild.to_be_removed', ['%leads%' => $leadCount, '%batch%' => $limit]));
        }

        if ($leadCount) {
            $maxCount = $maxLeads ?: $leadCount;

            if ($output) {
                $progress = ProgressBarHelper::init($output, $maxCount);
                $progress->start();
            }

            // Remove leads
            while ($start < $leadCount) {
                // Keep CPU down for large lists; sleep per $limit batch
                $this->batchSleep();

                $removeLeadList = $this->leadSegmentService->getOrphanedLeadListLeads($leadList, [], $limit);

                if (empty($removeLeadList[$segmentId])) {
                    // Somehow ran out of leads so break out
                    break;
                }

                $processedLeads = [];
                foreach ($removeLeadList[$segmentId] as $l) {
                    $this->removeLead($l, $leadList, false, true, true);
                    $processedLeads[] = $l;
                    ++$leadsProcessed;
                    if ($output && $leadsProcessed < $maxCount) {
                        $progress->setProgress($leadsProcessed);
                    }

                    if ($maxLeads && $leadsProcessed >= $maxLeads) {
                        break;
                    }
                }

                // Dispatch batch event
                if (count($processedLeads) && $this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_BATCH_CHANGE)) {
                    $this->dispatcher->dispatch(
                        new ListChangeEvent($processedLeads, $leadList, false),
                        LeadEvents::LEAD_LIST_BATCH_CHANGE
                    );
                }

                $start += $limit;

                unset($removeLeadList);

                // Free some memory
                gc_collect_cycles();

                if ($maxLeads && $leadsProcessed >= $maxLeads) {
                    if ($output) {
                        $progress->finish();
                        $output->writeln('');
                    }

                    return $leadsProcessed;
                }
            }

            if ($output) {
                $progress->finish();
                $output->writeln('');
            }
        }

        $totalLeadCount = $this->getRepository()->getLeadCount($segmentId);
        $this->segmentCountCacheHelper->setSegmentContactCount($segmentId, (int) $totalLeadCount);

        return $leadsProcessed;
    }

    /**
     * Add lead to lists.
     *
     * @param array|int|Lead $lead
     * @param array|LeadList $lists
     * @param bool           $manuallyAdded
     * @param bool           $batchProcess
     * @param int            $searchListLead  0 = reference, 1 = yes, -1 = known to not exist
     * @param \DateTime      $dateManipulated
     *
     * @throws \Exception
     */
    public function addLead($lead, $lists, $manuallyAdded = false, $batchProcess = false, $searchListLead = 1, $dateManipulated = null): void
    {
        if (null == $dateManipulated) {
            $dateManipulated = new \DateTime();
        }

        if (!$lead instanceof Lead) {
            $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
            $lead   = $this->em->getReference(\Mautic\LeadBundle\Entity\Lead::class, $leadId);
        } else {
            $leadId = $lead->getId();
        }

        if (!$lists instanceof LeadList) {
            // make sure they are ints
            $searchForLists = [];
            foreach ($lists as &$l) {
                $l = (int) $l;
                if (!isset($this->leadChangeLists[$l])) {
                    $searchForLists[] = $l;
                }
            }

            if (!empty($searchForLists)) {
                $listEntities = $this->getEntities([
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'l.id',
                                'expr'   => 'in',
                                'value'  => $searchForLists,
                            ],
                        ],
                    ],
                ]);

                foreach ($listEntities as $list) {
                    $this->leadChangeLists[$list->getId()] = $list;
                }
            }

            unset($listEntities, $searchForLists);
        } else {
            $this->leadChangeLists[$lists->getId()] = $lists;

            $lists = [$lists->getId()];
        }

        if (!is_array($lists)) {
            $lists = [$lists];
        }

        $persistLists   = [];
        $dispatchEvents = [];

        foreach ($lists as $listId) {
            if (!isset($this->leadChangeLists[$listId])) {
                // List no longer exists in the DB so continue to the next
                continue;
            }

            if (-1 == $searchListLead) {
                $listLead = null;
            } elseif ($searchListLead) {
                $listLead = $this->getListLeadRepository()->findOneBy(
                    [
                        'lead' => $lead,
                        'list' => $this->leadChangeLists[$listId],
                    ]
                );
            } else {
                $listLead = $this->em->getReference(ListLead::class,
                    [
                        'lead' => $leadId,
                        'list' => $listId,
                    ]
                );
            }

            if (null != $listLead) {
                if ($manuallyAdded && $listLead->wasManuallyRemoved()) {
                    $listLead->setManuallyRemoved(false);
                    $listLead->setManuallyAdded($manuallyAdded);

                    $persistLists[]   = $listLead;
                    $dispatchEvents[] = $listId;
                } else {
                    // Detach from Doctrine
                    $this->em->detach($listLead);

                    continue;
                }
            } else {
                $listLead = new ListLead();
                $listLead->setList($this->em->getReference(LeadList::class, $listId));
                $listLead->setLead($lead);
                $listLead->setManuallyAdded($manuallyAdded);
                $listLead->setDateAdded($dateManipulated);

                $persistLists[]   = $listLead;
                $dispatchEvents[] = $listId;
            }

            $this->segmentCountCacheHelper->incrementSegmentContactCount($listId);
        }

        if (!empty($persistLists)) {
            $this->getRepository()->saveEntities($persistLists);
        }

        // Clear ListLead entities from Doctrine memory
        $this->getRepository()->detachEntities($persistLists);

        if ($batchProcess) {
            // Detach for batch processing to preserve memory
            $this->em->detach($lead);
        } elseif (!empty($dispatchEvents) && $this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_CHANGE)) {
            foreach ($dispatchEvents as $listId) {
                $event = new ListChangeEvent($lead, $this->leadChangeLists[$listId]);
                $this->dispatcher->dispatch($event, LeadEvents::LEAD_LIST_CHANGE);

                unset($event);
            }
        }

        unset($lead, $persistLists, $lists);
    }

    /**
     * Remove a lead from lists.
     *
     * @param bool $manuallyRemoved
     * @param bool $batchProcess
     * @param bool $skipFindOne
     *
     * @throws \Exception
     */
    public function removeLead($lead, $lists, $manuallyRemoved = false, $batchProcess = false, $skipFindOne = false): void
    {
        if (!$lead instanceof Lead) {
            $leadId = (is_array($lead) && isset($lead['id'])) ? $lead['id'] : $lead;
            $lead   = $this->em->getReference(Lead::class, $leadId);
        } else {
            $leadId = $lead->getId();
        }

        if (!$lists instanceof LeadList) {
            // make sure they are ints
            $searchForLists = [];
            foreach ($lists as &$l) {
                $l = (int) $l;
                if (!isset($this->leadChangeLists[$l])) {
                    $searchForLists[] = $l;
                }
            }

            if (!empty($searchForLists)) {
                $listEntities = $this->getEntities([
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'l.id',
                                'expr'   => 'in',
                                'value'  => $searchForLists,
                            ],
                        ],
                    ],
                ]);

                foreach ($listEntities as $list) {
                    $this->leadChangeLists[$list->getId()] = $list;
                }
            }

            unset($listEntities, $searchForLists);
        } else {
            $this->leadChangeLists[$lists->getId()] = $lists;

            $lists = [$lists->getId()];
        }

        if (!is_array($lists)) {
            $lists = [$lists];
        }

        $persistLists   = [];
        $deleteLists    = [];
        $dispatchEvents = [];

        foreach ($lists as $listId) {
            if (!isset($this->leadChangeLists[$listId])) {
                // List no longer exists in the DB so continue to the next
                continue;
            }

            $listLead = (!$skipFindOne) ?
                $this->getListLeadRepository()->findOneBy([
                    'lead' => $lead,
                    'list' => $this->leadChangeLists[$listId],
                ]) :
                $this->em->getReference(ListLead::class, [
                    'lead' => $leadId,
                    'list' => $listId,
                ]);

            if (null == $listLead) {
                // Lead is not part of this list
                continue;
            }

            if (($manuallyRemoved && $listLead->wasManuallyAdded()) || (!$manuallyRemoved && !$listLead->wasManuallyAdded())) {
                // lead was manually added and now manually removed or was not manually added and now being removed
                $deleteLists[]    = $listLead;
                $dispatchEvents[] = $listId;
            } elseif ($manuallyRemoved && !$listLead->wasManuallyAdded()) {
                $listLead->setManuallyRemoved(true);

                $persistLists[]   = $listLead;
                $dispatchEvents[] = $listId;
            }

            $this->segmentCountCacheHelper->decrementSegmentContactCount($listId);

            unset($listLead);
        }

        if (!empty($persistLists)) {
            $this->getRepository()->saveEntities($persistLists);
        }

        if (!empty($deleteLists)) {
            $this->getRepository()->deleteEntities($deleteLists);
        }

        // Clear ListLead entities from Doctrine memory
        $this->getListLeadRepository()->detachEntities($persistLists);
        $this->getListLeadRepository()->detachEntities($deleteLists);

        if ($batchProcess) {
            // Detach for batch processing to preserve memory
            $this->em->detach($lead);
        } elseif (!empty($dispatchEvents) && $this->dispatcher->hasListeners(LeadEvents::LEAD_LIST_CHANGE)) {
            foreach ($dispatchEvents as $listId) {
                $event = new ListChangeEvent($lead, $this->leadChangeLists[$listId], false);
                $this->dispatcher->dispatch($event, LeadEvents::LEAD_LIST_CHANGE);

                unset($event);
            }
        }

        unset($lead, $deleteLists, $persistLists, $lists);
    }

    /**
     * Batch sleep according to settings.
     */
    protected function batchSleep()
    {
        $leadSleepTime = $this->coreParametersHelper->get('batch_lead_sleep_time', false);
        if (false === $leadSleepTime) {
            $leadSleepTime = $this->coreParametersHelper->get('batch_sleep_time', 1);
        }

        if (empty($leadSleepTime)) {
            return;
        }

        if ($leadSleepTime < 1) {
            usleep($leadSleepTime * 1_000_000);
        } else {
            sleep($leadSleepTime);
        }
    }

    /**
     * Get a list of top (by leads added) lists.
     *
     * @param int       $limit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param bool      $canViewOthers
     *
     * @return array
     */
    public function getTopLists($limit = 10, $dateFrom = null, $dateTo = null, $canViewOthers = true)
    {
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(t.date_added) AS leads, ll.id, ll.name, ll.alias')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'lead_lists', 'll', 'll.id = t.leadlist_id')
            ->orderBy('leads', 'DESC')
            ->where($q->expr()->eq('ll.is_published', ':published'))
            ->setParameter('published', true)
            ->groupBy('ll.id')
            ->setMaxResults($limit);

        if (!$canViewOthers) {
            $q->andWhere('ll.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $chartQuery = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $chartQuery->applyDateFilters($q, 'date_added');

        return $q->execute()->fetchAllAssociative();
    }

    /**
     * Get a list of top (by leads added) lists.
     *
     * @param int    $limit
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     */
    public function getLifeCycleSegments($limit, $dateFrom, $dateTo, $canViewOthers, $segments)
    {
        if (!empty($segments)) {
            $segmentlist = "'".implode("','", $segments)."'";
        }
        $q = $this->em->getConnection()->createQueryBuilder();
        $q->select('COUNT(t.date_added) AS leads, ll.id, ll.name as name,ll.alias as alias')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'lead_lists', 'll', 'll.id = t.leadlist_id')
            ->join('t', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = t.lead_id')
            ->orderBy('leads', 'DESC')
            ->where($q->expr()->eq('ll.is_published', ':published'))
            ->setParameter('published', true)
            ->groupBy('ll.id');

        if ($limit) {
            $q->setMaxResults($limit);
        }
        if (!empty($segments)) {
            $q->andWhere('ll.id IN ('.$segmentlist.')');
        }
        if (!empty($dateFrom)) {
            $q->andWhere("l.date_added >= '".$dateFrom->format('Y-m-d')."'");
        }
        if (!empty($dateTo)) {
            $q->andWhere("l.date_added <= '".$dateTo->format('Y-m-d')." 23:59:59'");
        }
        if (!$canViewOthers) {
            $q->andWhere('ll.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        if (in_array(0, $segments)) {
            $qAll = $this->em->getConnection()->createQueryBuilder();
            $qAll->select('COUNT(t.date_added) AS leads, 0 as id, "All Contacts" as name, "" as alias')
                ->from(MAUTIC_TABLE_PREFIX.'leads', 't');

            if (!$canViewOthers) {
                $qAll->andWhere('ll.created_by = :userId')
                    ->setParameter('userId', $this->userHelper->getUser()->getId());
            }
            if (!empty($dateFrom)) {
                $qAll->andWhere("t.date_added >= '".$dateFrom->format('Y-m-d')."'");
            }
            if (!empty($dateTo)) {
                $qAll->andWhere("t.date_added <= '".$dateTo->format('Y-m-d')." 23:59:59'");
            }
            $resultsAll = $qAll->executeQuery()->fetchAllAssociative();
            $results    = array_merge($results, $resultsAll);
        }

        return $results;
    }

    /**
     * @param bool $canViewOthers
     */
    public function getLifeCycleSegmentChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat, $filter, $canViewOthers, $listName): array
    {
        $chart = new PieChart();
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);

        if (!$canViewOthers) {
            $filter['owner_id'] = $this->userHelper->getUser()->getId();
        }

        if (isset($filter['flag'])) {
            unset($filter['flag']);
        }

        $allLists   = $query->getCountQuery('leads', 'id', 'date_added', null);
        $lists      = $query->count('leads', 'id', 'date_added', $filter, null);
        $all        = $query->fetchCount($allLists);
        $identified = $lists;

        $chart->setDataset($listName, $identified);

        if (isset($filter['leadlist_id']['value'])) {
            $chart->setDataset(
                $this->translator->trans('mautic.lead.lifecycle.graph.pie.all.lists'),
                $all
            );
        }

        return $chart->render(false);
    }

    /**
     * @param array $filter
     * @param bool  $canViewOthers
     */
    public function getStagesBarChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true): array
    {
        $data['values'] = [];
        $data['labels'] = [];

        $q = $this->em->getConnection()->createQueryBuilder();

        $q->select('count(l.id) as leads, s.name as stage')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = t.lead_id')
            ->join('t', MAUTIC_TABLE_PREFIX.'stages', 's', 's.id=l.stage_id')
            ->orderBy('leads', 'DESC')
            ->where($q->expr()->eq('s.is_published', ':published'))

            ->andWhere($q->expr()->gte('t.date_added', ':date_from'))
            ->setParameter('date_from', $dateFrom->format('Y-m-d'))
            ->andWhere($q->expr()->lte('t.date_added', ':date_to'))
            ->setParameter('date_to', $dateTo->format('Y-m-d 23:59:59'))
            ->setParameter('published', true);

        if (isset($filter['leadlist_id']['value'])) {
            $q->andWhere($q->expr()->eq('t.leadlist_id', ':leadlistid'))->setParameter('leadlistid', $filter['leadlist_id']['value']);
        }

        $q->groupBy('s.name');

        if (!$canViewOthers) {
            $q->andWhere('s.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        foreach ($results as $result) {
            $data['labels'][] = substr($result['stage'], 0, 12);
            $data['values'][] = $result['leads'];
        }
        $data['xAxes'][] = ['display' => true];
        $data['yAxes'][] = ['display' => true];

        $baseData = [
            'label' => $this->translator->trans('mautic.lead.leads'),
            'data'  => $data['values'],
        ];

        $chart      = new BarChart($data['labels']);
        $datasets[] = array_merge($baseData, $chart->generateColors(3));

        return [
            'labels'   => $data['labels'],
            'datasets' => $datasets,
            'options'  => [
                'xAxes' => $data['xAxes'],
                'yAxes' => $data['yAxes'],
            ], ];
    }

    /**
     * @param array $filter
     * @param bool  $canViewOthers
     */
    public function getDeviceGranularityData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true): array
    {
        $data['values'] = [];
        $data['labels'] = [];

        $q = $this->em->getConnection()->createQueryBuilder();

        $q->select('count(l.id) as leads, ds.device')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 't')
            ->join('t', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = t.lead_id')
            ->join('t', MAUTIC_TABLE_PREFIX.'page_hits', 'h', 'h.lead_id=l.id')
            ->join('h', MAUTIC_TABLE_PREFIX.'lead_devices', 'ds', 'ds.id = h.device_id')
            ->orderBy('ds.device', 'DESC')
            ->andWhere($q->expr()->gte('t.date_added', ':date_from'))
            ->setParameter('date_from', $dateFrom->format('Y-m-d'))
            ->andWhere($q->expr()->lte('t.date_added', ':date_to'))
            ->setParameter('date_to', $dateTo->format('Y-m-d 23:59:59'));

        if (isset($filter['leadlist_id']['value'])) {
            $q->andWhere($q->expr()->eq('t.leadlist_id', ':leadlistid'))->setParameter(
                'leadlistid',
                $filter['leadlist_id']['value']
            );
        }

        $q->groupBy('ds.device');

        if (!$canViewOthers) {
            $q->andWhere('l.created_by = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        foreach ($results as $result) {
            $data['labels'][] = substr(empty($result['device']) ? $this->translator->trans('mautic.core.no.info') : $result['device'], 0, 12);
            $data['values'][] = $result['leads'];
        }

        $data['xAxes'][] = ['display' => true];
        $data['yAxes'][] = ['display' => true];

        $baseData = [
            'label' => $this->translator->trans('mautic.core.device'),
            'data'  => $data['values'],
        ];

        $chart      = new BarChart($data['labels']);
        $datasets[] = array_merge($baseData, $chart->generateColors(2));

        return [
            'labels'   => $data['labels'],
            'datasets' => $datasets,
            'options'  => [
                'xAxes' => $data['xAxes'],
                'yAxes' => $data['yAxes'],
            ],
        ];
    }

    /**
     * Get line chart data of hits.
     *
     * @param string $unit       {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string $dateFormat
     * @param array  $filter
     */
    public function getSegmentContactsLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = []): array
    {
        $chart    = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query    = new SegmentContactsLineChartQuery($this->em->getConnection(), $dateFrom, $dateTo, $filter);

        // added line everytime
        $chart->setDataset($this->translator->trans('mautic.lead.segments.contacts.added'), $this->segmentChartQueryFactory->getContactsAdded($query));

        // Just if we have event log data
        // Added in 2.15 , then we can' display just from data from date range with event logs
        if ($query->isStatsFromEventLog()) {
            $chart->setDataset($this->translator->trans('mautic.lead.segments.contacts.removed'), $this->segmentChartQueryFactory->getContactsRemoved($query));
            $chart->setDataset($this->translator->trans('mautic.lead.segments.contacts.total'), $this->segmentChartQueryFactory->getContactsTotal($query, $this));
        }

        return $chart->render();
    }

    /**
     * Is custom field used in at least one defined segment?
     */
    public function isFieldUsed(LeadField $field): bool
    {
        return 0 < $this->getFieldSegments($field)->count();
    }

    public function getFieldSegments(LeadField $field)
    {
        $alias       = $field->getAlias();
        $aliasLength = mb_strlen($alias);
        $likeContent = "%;s:5:\"field\";s:{$aliasLength}:\"{$alias}\";%";
        $filter      = [
            'force'  => [
                ['column' => 'l.filters', 'expr' => 'LIKE', 'value'=> $likeContent],
            ],
        ];

        return $this->getEntities(['filter' => $filter]);
    }

    /**
     * @param $segmentId *
     *
     * @return array
     */
    public function getSegmentsWithDependenciesOnSegment($segmentId, $returnProperty = 'name')
    {
        $filter = [
            'force'  => [
                ['column' => 'l.filters', 'expr' => 'LIKE', 'value'=>'%s:8:"leadlist"%'],
                ['column' => 'l.id', 'expr' => 'neq', 'value'=>$segmentId],
            ],
        ];
        $entities = $this->getEntities(
            [
                'filter' => $filter,
            ]
        );
        $dependents = [];
        $accessor   = PropertyAccess::createPropertyAccessor();
        foreach ($entities as $entity) {
            $retrFilters = $entity->getFilters();
            foreach ($retrFilters as $eachFilter) {
                // BC support for old filters where the field existed outside of properties.
                $filter = $eachFilter['properties']['filter'] ?? $eachFilter['filter'];
                if ($filter && 'leadlist' === $eachFilter['type'] && in_array($segmentId, $filter)) {
                    if ($returnProperty && $value = $accessor->getValue($entity, $returnProperty)) {
                        $dependents[] = $value;
                    } else {
                        $dependents[] = $entity;
                    }
                    break;
                }
            }
        }

        return $dependents;
    }

    /**
     * Get segments which are used as a dependent by other segments to prevent batch deletion of them.
     *
     * @param array $segmentIds
     */
    public function canNotBeDeleted($segmentIds): array
    {
        $entities = $this->getEntities(
            [
                'filter' => [
                    'force'  => [
                        ['column' => 'l.filters', 'expr' => 'LIKE', 'value'=>'%s:8:"leadlist"%'],
                    ],
                ],
            ]
        );

        $idsNotToBeDeleted   = [];
        $namesNotToBeDeleted = [];
        $dependency          = [];

        foreach ($entities as $entity) {
            $retrFilters = $entity->getFilters();
            foreach ($retrFilters as $eachFilter) {
                if ('leadlist' !== $eachFilter['type']) {
                    continue;
                }

                $idsNotToBeDeleted = array_unique(array_merge($idsNotToBeDeleted, $eachFilter['filter']));
                $bcFilterValue     = $eachFilter['filter'] ?? [];
                $filterValue       = $eachFilter['properties']['filter'] ?? $bcFilterValue;
                foreach ($filterValue as $val) {
                    if (!empty($dependency[$val])) {
                        $dependency[$val] = array_merge($dependency[$val], [$entity->getId()]);
                        $dependency[$val] = array_unique($dependency[$val]);
                    } else {
                        $dependency[$val] = [$entity->getId()];
                    }
                }
            }
        }
        foreach ($dependency as $key => $value) {
            if (array_intersect($value, $segmentIds) === $value) {
                $idsNotToBeDeleted = array_unique(array_diff($idsNotToBeDeleted, [$key]));
            }
        }

        $idsNotToBeDeleted = array_intersect($segmentIds, $idsNotToBeDeleted);

        foreach ($idsNotToBeDeleted as $val) {
            $namesNotToBeDeleted[$val] = $this->getEntity($val)->getName();
        }

        return $namesNotToBeDeleted;
    }

    /**
     * Get a list of source choices.
     */
    public function getSourceLists(string $sourceType = null): array
    {
        $choices = [];
        switch ($sourceType) {
            case 'categories':
            case null:
                $choices['categories'] = [];
                $categories            = $this->categoryModel->getLookupResults('segment');
                foreach ($categories as $category) {
                    $choices['categories'][$category['id']] = $category['title'];
                }
        }

        foreach ($choices as &$typeChoices) {
            asort($typeChoices);
        }

        return (null == $sourceType) ? $choices : $choices[$sourceType];
    }

    /**
     * @param array<int> $listIds
     *
     * @return array<int>
     *
     * @throws \Exception
     */
    public function getSegmentContactCountFromCache(array $listIds): array
    {
        $leadCounts = [];

        foreach ($listIds as $listId) {
            $leadCounts[$listId] = $this->segmentCountCacheHelper->getSegmentContactCount($listId);
        }

        return $leadCounts;
    }

    public function leadListExists(int $id): bool
    {
        return $this->getRepository()->leadListExists($id);
    }

    /**
     * @param array<int> $listIds
     *
     * @return array<int>
     *
     * @throws \Exception
     */
    public function getSegmentContactCount(array $listIds): array
    {
        $leadCounts = [];

        foreach ($listIds as $listId) {
            if ($this->segmentCountCacheHelper->hasSegmentContactCount($listId)) {
                $leadCounts[$listId] = $this->segmentCountCacheHelper->getSegmentContactCount($listId);
            } else {
                $count               = $this->getRepository()->getLeadCount($listId);
                $leadCounts[$listId] = $count;
                $this->segmentCountCacheHelper->setSegmentContactCount($listId, $count);
            }
        }

        return $leadCounts;
    }

    /**
     * @param array<int,int> $segmentsFilter
     *
     * @return array<int,LeadList>
     */
    public function getSegmentsBuildTime(int $limit = 10, string $order = 'DESC', array $segmentsFilter = [], bool $canViewOthers = true): array
    {
        $criteria = ['isPublished' => true];

        if (!$canViewOthers) {
            $criteria['createdBy'] = $this->userHelper->getUser()->getId();
        }

        if (!empty($segmentsFilter)) {
            $criteria['id'] = $segmentsFilter;
        }

        return $this->getRepository()->findBy($criteria, ['lastBuiltTime' => $order], $limit);
    }
}
