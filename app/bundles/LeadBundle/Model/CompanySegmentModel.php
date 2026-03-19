<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\CompaniesSegments;
use Mautic\LeadBundle\Entity\CompaniesSegmentsRepository;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Entity\CompanySegmentRepository;
use Mautic\LeadBundle\Entity\OperatorListTrait;
use Mautic\LeadBundle\Event\CompanySegmentChangeEvent;
use Mautic\LeadBundle\Event\CompanySegmentFiltersChoicesEvent;
use Mautic\LeadBundle\Event\CompanySegmentPostDelete;
use Mautic\LeadBundle\Event\CompanySegmentPostSave;
use Mautic\LeadBundle\Event\CompanySegmentPreDelete;
use Mautic\LeadBundle\Event\CompanySegmentPreSave;
use Mautic\LeadBundle\Form\Type\CompanySegmentType;
use Mautic\LeadBundle\Helper\CompanySegmentCountCacheHelper;
use Mautic\LeadBundle\LeadEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
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
     * Add company to segments.
     *
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

            // If there will be a memory issue: this could be cached as in the lead segment method.
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

        // do not detach company, as it may be used in the subsequent requests.
    }

    /**
     * Remove company from segments.
     *
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
                // Company is not part of this segment
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

        // do not detach company, as it may be used in the subsequent requests.
    }

    /**
     * Get list of company segments for use in filter choices.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCompanySegments(string $alias = ''): array
    {
        $user = false === $this->security->isGranted($this->getPermissionBase().':viewother') ? $this->userHelper->getUser() : null;

        return $this->getRepository()->getSegments($user, $alias);
    }

    /**
     * Get field choices for company segment filters.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getChoiceFields(): array
    {
        if ([] !== $this->choiceFieldsCache) {
            return $this->choiceFieldsCache;
        }

        $choices = [];

        // Add custom choices via event
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
}
