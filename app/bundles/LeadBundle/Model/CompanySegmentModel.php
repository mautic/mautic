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
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Entity\CompanySegmentRepository;
use Mautic\LeadBundle\Event\CompanySegmentPostDelete;
use Mautic\LeadBundle\Event\CompanySegmentPostSave;
use Mautic\LeadBundle\Event\CompanySegmentPreDelete;
use Mautic\LeadBundle\Event\CompanySegmentPreSave;
use Mautic\LeadBundle\Form\Type\CompanySegmentType;
use Mautic\LeadBundle\Helper\CompanySegmentCountCacheHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends FormModel<CompanySegment>
 */
class CompanySegmentModel extends FormModel
{
    public const PROPERTIES_FIELD = CompanySegment::TABLE_NAME;
    public const SEARCH_COMMAND   = 'mautic.company_segments.searchcommand.list';

    public function __construct(
        private CompanySegmentCountCacheHelper $companySegmentCountCacheHelper,
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
            $companyCounts[$segmentId] = $this->companySegmentCountCacheHelper->getSegmentCompanyCount($segmentId);
        }

        return $companyCounts;
    }

    public function hasSegmentCompanyCountInCache(int $segmentId): bool
    {
        return $this->companySegmentCountCacheHelper->hasSegmentCompanyCount($segmentId);
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
            $this->companySegmentCountCacheHelper->setSegmentCompanyCount($segmentId, $count);
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
}
