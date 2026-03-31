<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Model;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Exception\DeleteEntityDependencyException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\CompaniesSegments;
use Mautic\LeadBundle\Entity\CompaniesSegmentsRepository;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Entity\CompanySegmentRepository;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Event\CompanySegmentChangeEvent;
use Mautic\LeadBundle\Event\CompanySegmentFiltersChoicesEvent;
use Mautic\LeadBundle\Event\CompanySegmentPostDelete;
use Mautic\LeadBundle\Event\CompanySegmentPostSave;
use Mautic\LeadBundle\Event\CompanySegmentPreDelete;
use Mautic\LeadBundle\Event\CompanySegmentPreSave;
use Mautic\LeadBundle\Event\CompanySegmentPreUnpublish;
use Mautic\LeadBundle\Event\CompanySegmentRebuildAddEvent;
use Mautic\LeadBundle\Event\CompanySegmentRebuildRemoveEvent;
use Mautic\LeadBundle\Event\SegmentPreRebuildSegmentEvent;
use Mautic\LeadBundle\Form\Type\CompanySegmentType;
use Mautic\LeadBundle\Helper\CompanySegmentCountCacheHelper;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Services\CompanySegmentService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<CompanySegment>
 */
class CompanySegmentModel extends FormModel
{
    use OperatorListTrait;

    public const PROPERTIES_FIELD = CompanySegment::TABLE_NAME;
    public const SEARCH_COMMAND   = 'mautic.company_segments.searchcommand.list';

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $choiceFieldsCache = [];

    public function __construct(
        private CompanySegmentCountCacheHelper $companySegmentCountCacheHelper,
        private RequestStack $requestStack,
        private CompanySegmentService $companySegmentService,
        private ListModel $listModel,
        private Connection $connection,
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    public function getRepository(): CompanySegmentRepository
    {
        $repository = $this->em->getRepository(CompanySegment::class);
        \assert($repository instanceof CompanySegmentRepository);

        return $repository;
    }

    public function getCompaniesSegmentsRepository(): CompaniesSegmentsRepository
    {
        $repository = $this->em->getRepository(CompaniesSegments::class);
        \assert($repository instanceof CompaniesSegmentsRepository);

        return $repository;
    }

    protected function getCacheHelper(): CompanySegmentCountCacheHelper
    {
        return $this->companySegmentCountCacheHelper;
    }

    /**
     * @param CompanySegment $entity
     * @param bool           $unlock
     */
    public function saveEntity($entity, $unlock = true): void
    {
        $isNew = null === $entity->getId();

        // set some defaults
        $this->setTimestamps($entity, $isNew, $unlock);
        assert($entity instanceof CompanySegment);
        $alias = $entity->getAlias();
        if (is_null($alias)) {
            $alias = '';
        }

        $alias = $this->cleanAlias($alias, '', 0, '-');

        // make sure alias is not already taken
        $repo      = $this->getRepository();
        $testAlias = $alias;
        $existing  = $repo->getSegments(null, $testAlias, $entity->getId());
        $count     = count($existing);
        $aliasTag  = $count;

        while ($count > 0) {
            $testAlias = $alias.$aliasTag;
            $existing  = $repo->getSegments(null, $testAlias, $entity->getId());
            $count     = count($existing);
            ++$aliasTag;
        }
        if ($testAlias !== $alias) {
            $alias = $testAlias;
        }
        $entity->setAlias($alias);

        $this->dispatchEvent('pre_save', $entity, $isNew);
        $repo->saveEntity($entity);
        $this->dispatchEvent('post_save', $entity, $isNew);
    }

    /**
     * @throws DeleteEntityDependencyException
     */
    public function deleteEntity($entity): void
    {
        if (!$entity instanceof CompanySegment) {
            throw new \InvalidArgumentException('Entity must be of class CompanySegment');
        }

        $id = $entity->getId();
        if (null === $id) {
            throw new \InvalidArgumentException('Entity must have an ID');
        }

        $dependentsCompanySegments = $this->getSegmentsWithDependenciesOnSegment($id, 'name');
        if ([] !== $dependentsCompanySegments) {
            throw new DeleteEntityDependencyException($dependentsCompanySegments, implode(', ', $dependentsCompanySegments));
        }

        $dependentsContactSegments = $this->getSegmentsWithDependenciesOnSegment($id, 'name', true);
        if ([] !== $dependentsContactSegments) {
            throw new DeleteEntityDependencyException($dependentsContactSegments, implode(', ', $dependentsContactSegments));
        }

        // Proceed with deletion
        parent::deleteEntity($entity);
    }

    protected function dispatchEvent($action, &$entity, $isNew = false, ?Event $event = null): ?Event
    {
        if (!$entity instanceof CompanySegment) {
            throw new MethodNotAllowedHttpException(['CompanySegment'], 'Entity must be of class CompanySegment()');
        }

        $eventClass = match ($action) {
            'pre_save'      => CompanySegmentPreSave::class,
            'post_save'     => CompanySegmentPostSave::class,
            'pre_delete'    => CompanySegmentPreDelete::class,
            'post_delete'   => CompanySegmentPostDelete::class,
            'pre_unpublish' => CompanySegmentPreUnpublish::class,
            default         => null,
        };

        if (null === $eventClass && null === $event) {
            throw new \RuntimeException('Either the Event or proper action should be provided.');
        }

        if ($this->dispatcher->hasListeners($eventClass ?? $event::class)) {
            if (null === $event) {
                if (!class_exists($eventClass)) {
                    throw new \RuntimeException('The class '.$eventClass.' does not exist.');
                }

                if (in_array($eventClass, [CompanySegmentPreSave::class, CompanySegmentPostSave::class], true)) {
                    $event = new $eventClass($entity, $this->em, $isNew);
                } else {
                    $event = new $eventClass($entity, $this->em);
                }
            }
            $this->dispatcher->dispatch($event);

            return $event;
        }

        return null;
    }

    public function getPermissionBase(): string
    {
        return 'lead:lists';
    }

    /**
     * @param int|null $id
     */
    public function getEntity($id = null): ?object
    {
        if (null === $id) {
            return new CompanySegment();
        }

        return parent::getEntity($id);
    }

    /**
     * @param array<int> $segmentIds
     *
     * @return array<int, int>
     */
    public function getSegmentCompanyCountFromCache(array $segmentIds): array
    {
        $companyCounts = [];
        foreach ($segmentIds as $segmentId) {
            $companyCounts[$segmentId] = $this->getCacheHelper()->getSegmentCompanyCount($segmentId);
        }

        return $companyCounts;
    }

    public function hasSegmentCompanyCountInCache(int $segmentId): bool
    {
        return $this->getCacheHelper()->hasSegmentCompanyCount($segmentId);
    }

    /**
     * @param array<int> $segmentIds
     */
    public function setSegmentCompanyCountInCache(array $segmentIds): void
    {
        foreach ($segmentIds as $segmentId) {
            $companySegment = $this->getRepository()->find($segmentId);
            \assert($companySegment instanceof CompanySegment);
            $count = $companySegment->getCompaniesSegments()->count();
            $this->getCacheHelper()->setSegmentCompanyCount($segmentId, $count);
        }
    }

    /**
     * @param array<mixed> $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): FormInterface
    {
        if (!$entity instanceof CompanySegment) {
            throw new MethodNotAllowedHttpException(['CompanySegment'], 'Entity must be of class CompanySegment()');
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(CompanySegmentType::class, $entity, $options);
    }

    /**
     * @param iterable<CompanySegment|int> $companySegments
     */
    public function addCompany(Company $company, iterable $companySegments, bool $manuallyAdded = false, ?\DateTimeInterface $dateTimeManipulated = null): void
    {
        if (null === $dateTimeManipulated) {
            $dateTimeManipulated = new \DateTime();
        }

        if (is_array($companySegments) && is_numeric(current($companySegments))) {
            foreach ($companySegments as $index => $segmentId) {
                \assert(is_numeric($segmentId));
                $companySegments[$index] = (int) $segmentId;
            }

            $companySegments = $this->getEntities([
                'filter' => [
                    'force' => [
                        [
                            'column' => CompanySegment::DEFAULT_ALIAS.'.id',
                            'expr'   => 'in',
                            'value'  => $companySegments,
                        ],
                    ],
                ],
            ]);
        }

        $companyAddSegment = [];
        foreach ($companySegments as $companySegment) {
            assert($companySegment instanceof CompanySegment);
            if ($companySegment->hasCompany($company)) {
                continue;
            }

            $companiesSegments = $this->getCompaniesSegmentsRepository()->findOneBy(
                [
                    'company'        => $company,
                    'companySegment' => $companySegment,
                ]
            );

            if (null !== $companiesSegments) {
                if ($manuallyAdded && $companiesSegments->isManuallyRemoved()) {
                    $companiesSegments->setManuallyRemoved(false);
                    $companiesSegments->setManuallyAdded(true);
                } else {
                    // Detach from Doctrine, because the segment was manually removed and now is not manually added.
                    $this->em->detach($companiesSegments);

                    continue;
                }
            } else {
                $companiesSegments = new CompaniesSegments();

                $companiesSegments->setCompanySegment($companySegment);
                $companiesSegments->setCompany($company);
                $companiesSegments->setManuallyAdded($manuallyAdded);
                $companiesSegments->setDateAdded($dateTimeManipulated);
            }

            $companySegment->addCompaniesSegment($companiesSegments);

            $companyAddSegment[] = $companiesSegments;
            if (is_int($companySegment->getId())) {
                $this->getCacheHelper()->incrementSegmentCompanyCount($companySegment->getId());
            }
        }

        foreach ($companyAddSegment as $companiesSegment) {
            $event = new CompanySegmentChangeEvent($company, $companiesSegment->getCompanySegment(), true, $dateTimeManipulated);
            $this->dispatcher->dispatch($event);

            unset($event);
        }

        if ([] !== $companyAddSegment) {
            $this->getCompaniesSegmentsRepository()->saveEntities($companyAddSegment);
        }
    }

    /**
     * @param iterable<CompanySegment|int> $companySegments
     */
    public function removeCompany(Company $company, iterable $companySegments, bool $manuallyRemoved = false, bool $forceRemove = false): void
    {
        if (is_array($companySegments) && is_numeric(current($companySegments))) {
            foreach ($companySegments as $index => $segmentId) {
                \assert(is_numeric($segmentId));
                $companySegments[$index] = (int) $segmentId;
            }

            $companySegments = $this->getEntities([
                'filter' => [
                    'force' => [
                        [
                            'column' => CompanySegment::DEFAULT_ALIAS.'.id',
                            'expr'   => 'in',
                            'value'  => $companySegments,
                        ],
                    ],
                ],
            ]);
        }

        $companySaveSegment   = [];
        $companyDeleteSegment = [];
        foreach ($companySegments as $companySegment) {
            assert($companySegment instanceof CompanySegment);
            $companiesSegments = $this->getCompaniesSegmentsRepository()->findOneBy(
                [
                    'company'        => $company,
                    'companySegment' => $companySegment,
                ]
            );

            if (null === $companiesSegments) {
                continue;
            }
            if ($forceRemove || ($manuallyRemoved && $companiesSegments->isManuallyAdded()) || (!$manuallyRemoved && !$companiesSegments->isManuallyAdded())) {
                // Company was manually added and now manually removed or was not manually added and now being removed
                $companyDeleteSegment[$companySegment->getId()] = $companiesSegments;
                $companySegment->removeCompaniesSegment($companiesSegments);
            } elseif ($manuallyRemoved && !$companiesSegments->isManuallyAdded()) {
                $companiesSegments->setManuallyRemoved(true);

                $companySaveSegment[$companySegment->getId()] = $companiesSegments;
            }
            if (is_int($companySegment->getId())) {
                $this->getCacheHelper()->decrementSegmentCompanyCount($companySegment->getId());
            }
        }

        if ([] !== $companySaveSegment) {
            $this->getRepository()->saveEntities($companySaveSegment);
        }

        if ([] !== $companyDeleteSegment) {
            $this->getRepository()->deleteEntities($companyDeleteSegment);
        }

        $this->getCompaniesSegmentsRepository()->detachEntities($companySaveSegment);
        $this->getCompaniesSegmentsRepository()->detachEntities($companyDeleteSegment);

        foreach (array_merge($companySaveSegment, $companyDeleteSegment) as $companiesSegment) {
            $event = new CompanySegmentChangeEvent($company, $companiesSegment->getCompanySegment(), false);
            $this->dispatcher->dispatch($event);

            unset($event);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCompanySegments(string $alias = ''): array
    {
        $user = false === $this->security->isGranted($this->getPermissionBase().':viewother') ? $this->userHelper->getUser() : null;

        return $this->getRepository()->getSegments($user, $alias);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getChoiceFields(): array
    {
        if ([] !== $this->choiceFieldsCache) {
            return $this->choiceFieldsCache;
        }

        $choices = [];

        if ($this->dispatcher->hasListeners(LeadEvents::COMPANY_SEGMENT_FILTERS_CHOICES_ON_GENERATE)) {
            $operatorsForFieldType = $this->getOperatorsForFieldType();

            $event = new CompanySegmentFiltersChoicesEvent([], $operatorsForFieldType, $this->translator, $this->requestStack->getCurrentRequest());
            $this->dispatcher->dispatch($event, LeadEvents::COMPANY_SEGMENT_FILTERS_CHOICES_ON_GENERATE);
            $choices = $event->getChoices();
        }

        // Order choices by label
        /** @var array<string, array<string, mixed>> $choices */
        foreach ($choices as $key => $choice) {
            if (!is_array($choice)) {
                // skip invalid choice set
                continue;
            }

            $getLabel = static function ($item): string {
                if (is_array($item) && array_key_exists('label', $item)) {
                    $label = $item['label'];
                    if (is_string($label)) {
                        return $label;
                    }
                    if (is_numeric($label) || is_bool($label)) {
                        return (string) $label;
                    }

                    return '';
                }

                if (is_string($item)) {
                    return $item;
                }

                if (is_numeric($item) || is_bool($item)) {
                    return (string) $item;
                }

                return '';
            };
            $cmp = static function ($a, $b) use ($getLabel): int {
                return strcmp($getLabel($a), $getLabel($b));
            };

            uasort($choice, $cmp);
            $choices[$key] = $choice;
        }

        $this->choiceFieldsCache = $choices;

        return $choices;
    }

    public function getCompanyRepository(): CompanyRepository
    {
        $repository = $this->em->getRepository(Company::class);
        \assert($repository instanceof CompanyRepository);

        return $repository;
    }

    public function rebuildCompanySegment(CompanySegment $companySegment, int $limit = 100, ?int $max = null, ?OutputInterface $output = null): int
    {
        $segmentId = $companySegment->getId();
        \assert(null !== $segmentId);

        $dtHelper = new DateTimeHelper();

        $batchLimiters = ['dateTime' => $dtHelper->toUtcString()];
        $list          = ['id' => $segmentId, 'filters' => $companySegment->getFilters()];

        $this->dispatcher->dispatch(new SegmentPreRebuildSegmentEvent($list, false));

        try {
            $newCompaniesCount = $this->companySegmentService->getNewCompanySegmentsCompanyCount($companySegment, $batchLimiters);
        } catch (\Mautic\LeadBundle\Segment\Exception\FieldNotFoundException) {
            return 0;
        } catch (\Mautic\LeadBundle\Segment\Exception\SegmentNotFoundException) {
            return 0;
        } catch (\Mautic\LeadBundle\Segment\Exception\TableNotFoundException $e) {
            $this->logger->error($e->getMessage());

            return 0;
        }

        \assert(is_numeric($newCompaniesCount[$segmentId]['maxId']));
        $batchLimiters['maxId'] = (int) $newCompaniesCount[$segmentId]['maxId'];

        \assert(is_numeric($newCompaniesCount[$segmentId]['count']));
        $companiesCount = (int) $newCompaniesCount[$segmentId]['count'];

        if (0 === $companiesCount) {
            $this->logger->info('Company Segment QB - No new companies for segment found.');
        }

        if (null !== $output) {
            $output->writeln($this->translator->trans('mautic.company_segments.rebuild.to_be_added', ['%companies%' => $companiesCount, '%batch%' => $limit]));
        }

        $start = $companiesProcessed = 0;

        gc_enable();

        if ($companiesCount > 0) {
            $maxCount = $max > 0 ? $max : $companiesCount;

            if (null !== $output) {
                $progress = ProgressBarHelper::init($output, $maxCount);
                $progress->start();
            }

            while ($start < $companiesCount) {
                $this->batchSleep();

                $this->logger->debug(sprintf('Company Segment QB - Fetching new companies for segment [%d] %s', $segmentId, $companySegment->getName()));
                $newCompaniesSegments = $this->companySegmentService->getNewCompanySegmentCompanies($companySegment, $batchLimiters, $limit);

                if ([] === $newCompaniesSegments[$segmentId]) {
                    break;
                }

                $processedCompanies = [];
                $this->logger->debug(sprintf('Company Segment QB - Adding %d new companies to segment [%d] %s', count($newCompaniesSegments[$segmentId]), $segmentId, $companySegment->getName()));
                foreach ($newCompaniesSegments[$segmentId] as $companyProperties) {
                    \assert(is_array($companyProperties));
                    $companyId = $companyProperties['id'];
                    \assert(is_numeric($companyId));
                    $companyId = (int) $companyId;

                    $this->logger->debug(sprintf('Company Segment QB - Adding company #%s to segment [%d] %s', $companyId, $segmentId, $companySegment->getName()));

                    $company = $this->getCompanyRepository()->getEntity($companyId);
                    if (null === $company) {
                        $this->logger->info(sprintf('Company Segment QB - Can not find a company #%s to add to segment [%d] %s', $companyId, $segmentId, $companySegment->getName()));
                        continue;
                    }

                    $this->addCompany($company, [$companySegment], false, $dtHelper->getLocalDateTime());
                    $processedCompanies[] = $company;

                    ++$companiesProcessed;
                    if (null !== $output && $companiesProcessed < $maxCount && isset($progress)) {
                        $progress->setProgress($companiesProcessed);
                    }

                    if ($max > 0 && $companiesProcessed >= $max) {
                        break;
                    }
                }

                $this->logger->info(sprintf('Company Segment QB - Added %d new companies to segment [%d] %s', count($newCompaniesSegments[$segmentId]), $segmentId, $companySegment->getName()));

                $start += $limit;

                if (count($processedCompanies) > 0 && $this->dispatcher->hasListeners(CompanySegmentRebuildAddEvent::class)) {
                    $this->dispatcher->dispatch(
                        new CompanySegmentRebuildAddEvent($processedCompanies, $companySegment),
                    );
                }

                unset($newCompaniesSegments);

                gc_collect_cycles();

                if ($max > 0 && $companiesProcessed >= $max) {
                    if (null !== $output) {
                        $progress->finish();
                        $output->writeln('');
                    }

                    return $companiesProcessed;
                }
            }

            if (null !== $output) {
                $progress->finish();
                $output->writeln('');
            }
        }

        unset($batchLimiters['maxId']);

        $orphanCompaniesCount = $this->companySegmentService->getOrphanedCompanySegmentCompaniesCount($companySegment);

        \assert(is_numeric($orphanCompaniesCount[$segmentId]['maxId']));
        $batchLimiters['maxId'] = (int) $orphanCompaniesCount[$segmentId]['maxId'];

        $start = 0;
        \assert(is_numeric($orphanCompaniesCount[$segmentId]['count']));
        $companiesCount = (int) $orphanCompaniesCount[$segmentId]['count'];

        if (null !== $output) {
            $output->writeln($this->translator->trans('mautic.company_segments.rebuild.to_be_removed', ['%companies%' => $companiesCount, '%batch%' => $limit]));
        }

        if ($companiesCount > 0) {
            $maxCount = $max > 0 ? $max : $companiesCount;

            if (null !== $output) {
                $progress = ProgressBarHelper::init($output, $maxCount);
                $progress->start();
            }

            while ($start < $companiesCount) {
                $this->batchSleep();

                $removeCompanySegment = $this->companySegmentService->getOrphanedCompanySegmentCompanies($companySegment, $batchLimiters, $limit);

                if ([] === $removeCompanySegment[$segmentId]) {
                    break;
                }

                $processedCompanies = [];
                foreach ($removeCompanySegment[$segmentId] as $companyProperties) {
                    \assert(is_array($companyProperties));
                    $companyId = $companyProperties['id'];
                    \assert(is_numeric($companyId));
                    $companyId = (int) $companyId;

                    $company = $this->getCompanyRepository()->getEntity($companyId);
                    if (null === $company) {
                        $this->logger->info(sprintf('Company Segment QB - Can not find a company #%s to add to segment [%d] %s', $companyId, $segmentId, $companySegment->getName()));
                        continue;
                    }

                    $this->removeCompany($company, [$companySegment]);
                    $processedCompanies[] = $company;
                    ++$companiesProcessed;
                    if (null !== $output && isset($progress) && $companiesProcessed < $maxCount) {
                        $progress->setProgress($companiesProcessed);
                    }

                    if ($max > 0 && $companiesProcessed >= $max) {
                        break;
                    }
                }

                if (count($processedCompanies) > 0 && $this->dispatcher->hasListeners(CompanySegmentRebuildRemoveEvent::class)) {
                    $this->dispatcher->dispatch(
                        new CompanySegmentRebuildRemoveEvent($processedCompanies, $companySegment),
                    );
                }

                $start += $limit;

                unset($removeCompanySegment);

                gc_collect_cycles();

                if ($max > 0 && $companiesProcessed >= $max) {
                    if (null !== $output && isset($progress)) {
                        $progress->finish();
                        $output->writeln('');
                    }

                    return $companiesProcessed;
                }
            }

            if (null !== $output && isset($progress)) {
                $progress->finish();
                $output->writeln('');
            }
        }

        $totalCompaniesCount = $this->getCompaniesSegmentsRepository()->getCompanyCount([$segmentId]);
        $this->companySegmentCountCacheHelper->setSegmentCompanyCount($segmentId, $totalCompaniesCount[$segmentId] ?? 0);

        return $companiesProcessed;
    }

    private function batchSleep(): void
    {
        $leadSleepTime = $this->coreParametersHelper->get('batch_lead_sleep_time', false);
        if (false === $leadSleepTime) {
            $leadSleepTime = $this->coreParametersHelper->get('batch_sleep_time', 1);
        }

        if (false === $leadSleepTime || '' === $leadSleepTime || !is_numeric($leadSleepTime)) {
            return;
        }

        $leadSleepTime = (int) $leadSleepTime;

        if ($leadSleepTime < 1) {
            usleep($leadSleepTime * 1_000_000);
        } else {
            sleep($leadSleepTime);
        }
    }

    /**
     * @return array<int, mixed>
     */
    public function getSegmentsWithDependenciesOnSegment(int $segmentId, string $returnProperty = 'name', bool $isContactSegment = false): array
    {
        $tableAlias = $isContactSegment
            ? $this->listModel->getRepository()->getTableAlias()
            : $this->getRepository()->getTableAlias();

        $filter = [
            'force' => [
                [
                    'column' => $tableAlias.'.filters',
                    'expr'   => 'LIKE',
                    'value'  => $isContactSegment
                        ? '%"type";s:16:"company_segments"%'
                        : $this->getLikeQueryCompanySegment(),
                ],
                [
                    'column' => $tableAlias.'.id',
                    'expr'   => 'neq',
                    'value'  => $segmentId,
                ],
            ],
        ];

        $entities = $isContactSegment
            ? $this->listModel->getEntities(['filter' => $filter])
            : $this->getEntities(['filter' => $filter]);
        $dependents = [];
        $accessor   = PropertyAccess::createPropertyAccessor();
        foreach ($entities as $entity) {
            assert($entity instanceof CompanySegment || $entity instanceof LeadList);
            $retrFilters = $entity->getFilters();
            foreach ($retrFilters as $eachFilter) {
                if (!is_array($eachFilter) || !array_key_exists('properties', $eachFilter) || !is_array($eachFilter['properties']) || !array_key_exists('filter', $eachFilter['properties'])) {
                    continue;
                }
                $filter = $eachFilter['properties']['filter'];
                if (is_array($filter) && self::PROPERTIES_FIELD === $eachFilter['type'] && in_array($segmentId, $filter, true)) {
                    $value = $accessor->getValue($entity, $returnProperty);
                    if (('id' !== $returnProperty && !is_string($value)) || ('id' === $returnProperty && !is_numeric($value))) {
                        continue;
                    }
                    $dependents[] = $value;
                    break;
                }
            }
        }

        return $dependents;
    }

    /**
     * @param array<int> $segmentIds
     *
     * @return array<string>
     */
    public function canNotBeDeleted(array $segmentIds): array
    {
        $tableAlias = $this->getRepository()->getTableAlias();
        $segmentIds = array_map('intval', $segmentIds);

        $entities = $this->getEntities(
            [
                'filter' => [
                    'force'  => [
                        ['column' => $tableAlias.'.filters', 'expr' => 'LIKE', 'value' => $this->getLikeQueryCompanySegment()],
                    ],
                ],
            ]
        );

        $idsNotToBeDeleted   = [];
        $namesNotToBeDeleted = [];
        $filterRegistered    = [];

        foreach ($entities as $entity) {
            assert($entity instanceof CompanySegment);
            $retrFilters = $entity->getFilters();
            foreach ($retrFilters as $eachFilter) {
                if (
                    !is_array($eachFilter)
                    || !array_key_exists('type', $eachFilter)
                    || self::PROPERTIES_FIELD !== $eachFilter['type']
                    || !is_array($eachFilter['properties'])
                    || !array_key_exists('filter', $eachFilter['properties'])
                ) {
                    continue;
                }

                /** @var array<int> $filterValue */
                $filterValue       = $eachFilter['properties']['filter'];
                $idsNotToBeDeleted = $this->addIdsNotToBeDeleted($idsNotToBeDeleted, $filterValue);
                foreach ($filterValue as $valFilter) {
                    if (!isset($filterRegistered[$valFilter])) {
                        $filterRegistered[$valFilter][] = (int) $entity->getId();
                        continue;
                    }
                    if (in_array($entity->getId(), $filterRegistered[$valFilter], true)) {
                        continue;
                    }
                    $filterRegistered[$valFilter][] = (int) $entity->getId();
                }
            }
        }

        foreach ($filterRegistered as $keyValueFilter => $value) {
            if (array_intersect($value, $segmentIds) === $value) {
                $idsNotToBeDeleted = array_unique(array_diff($idsNotToBeDeleted, [$keyValueFilter]));
            }
        }

        $idsNotToBeDeleted = array_intersect($segmentIds, $idsNotToBeDeleted);

        foreach ($idsNotToBeDeleted as $val) {
            $notToBeDeletedEntity = $this->getEntity($val);
            assert($notToBeDeletedEntity instanceof CompanySegment);

            $name = $notToBeDeletedEntity->getName();

            if (null === $name) {
                $name = 'N/A';
            }

            $namesNotToBeDeleted[$val] = $name;
        }

        return $namesNotToBeDeleted;
    }

    /**
     * @param array<int> $segmentIds
     *
     * @return array<int, string>
     */
    public function canNotBeDeletedByContactSegments(array $segmentIds): array
    {
        $tableAlias = $this->listModel->getRepository()->getTableAlias();
        $segmentIds = array_map('intval', $segmentIds);

        $entities = $this->listModel->getEntities(
            [
                'filter' => [
                    'force'  => [
                        ['column' => $tableAlias.'.filters', 'expr' => 'LIKE', 'value' => $this->getLikeQueryCompanySegment()],
                    ],
                ],
            ]
        );

        if ([] === $entities) {
            return [];
        }

        $idsNotToBeDeleted   = [];
        $namesNotToBeDeleted = [];
        $filterRegistered    = [];

        foreach ($entities as $entity) {
            assert($entity instanceof LeadList);
            $retrFilters = $entity->getFilters();
            foreach ($retrFilters as $eachFilter) {
                if (
                    !is_array($eachFilter)
                    || !array_key_exists('type', $eachFilter)
                    || self::PROPERTIES_FIELD !== $eachFilter['type']
                    || !is_array($eachFilter['properties'])
                    || !array_key_exists('filter', $eachFilter['properties'])
                ) {
                    continue;
                }

                /** @var array<int> $filterValue */
                $filterValue       = $eachFilter['properties']['filter'];
                $idsNotToBeDeleted = $this->addIdsNotToBeDeleted($idsNotToBeDeleted, $filterValue);
                foreach ($filterValue as $valFilter) {
                    if (!isset($filterRegistered[$valFilter])) {
                        $filterRegistered[$valFilter][] = (int) $entity->getId();
                        continue;
                    }
                    if (in_array($entity->getId(), $filterRegistered[$valFilter], true)) {
                        continue;
                    }
                    $filterRegistered[$valFilter][] = (int) $entity->getId();
                }
            }
        }

        foreach ($filterRegistered as $keyValueFilter => $value) {
            if (array_intersect($value, $segmentIds) === $value) {
                $idsNotToBeDeleted = array_unique(array_diff($idsNotToBeDeleted, [$keyValueFilter]));
            }
        }

        $idsNotToBeDeleted = array_intersect($segmentIds, $idsNotToBeDeleted);

        foreach ($idsNotToBeDeleted as $val) {
            $notToBeDeletedEntity = $this->getEntity($val);
            assert($notToBeDeletedEntity instanceof CompanySegment);

            $name = $notToBeDeletedEntity->getName();

            if (null === $name) {
                $name = 'N/A';
            }

            $namesNotToBeDeleted[$val] = $name;
        }

        return $namesNotToBeDeleted;
    }

    private function getLikeQueryCompanySegment(): string
    {
        $platform  = $this->connection->getDatabasePlatform();
        $isMariaDb = $platform instanceof MariaDBPlatform;

        return $isMariaDb ? '%"type":"company_segments"%' : '%"type": "company_segments"%';
    }

    /**
     * @param array<int> $idsNotToBeDeleted
     * @param array<int> $newIds
     *
     * @return array<int>
     */
    private function addIdsNotToBeDeleted(array $idsNotToBeDeleted, array $newIds): array
    {
        foreach ($newIds as $id) {
            if (!is_numeric($id)) {
                continue;
            }
            $idsNotToBeDeleted[] = (int) $id;
        }

        return $idsNotToBeDeleted;
    }
}
