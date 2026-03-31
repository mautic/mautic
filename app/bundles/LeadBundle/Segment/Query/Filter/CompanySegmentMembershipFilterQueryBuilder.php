<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\Exception\SegmentNotFoundException;
use Mautic\LeadBundle\Exception\SegmentQueryException;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\LeadBundle\Segment\Query\CompanySegmentQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles Company Segment membership filters within Company Segments and Lead Segments.
 *
 * This filter allows:
 * - Filtering companies by their membership in other company segments
 * - Filtering leads by their primary company's segment membership
 */
class CompanySegmentMembershipFilterQueryBuilder extends BaseFilterQueryBuilder implements EventSubscriberInterface
{
    public function __construct(
        RandomParameterName $randomParameterNameService,
        private CompanySegmentQueryBuilder $companySegmentQueryBuilder,
        private EntityManager $entityManager,
        private ContactSegmentFilterFactory $contactSegmentFilterFactory,
        EventDispatcherInterface $dispatcher,
    ) {
        parent::__construct($randomParameterNameService, $dispatcher);
    }

    public static function getServiceId(): string
    {
        return self::class;
    }

    /**
     * @throws SegmentNotFoundException
     * @throws SegmentQueryException
     * @throws \Doctrine\DBAL\Exception
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        if (CompanySegmentModel::PROPERTIES_FIELD !== $filter->getField()) {
            throw new \RuntimeException('The supported field is '.CompanySegmentModel::PROPERTIES_FIELD);
        }

        $from = $queryBuilder->getQueryPart('from');
        assert(is_array($from));

        if (
            array_key_exists(0, $from)
            && is_array($from[0])
            && array_key_exists('table', $from[0])
            && $from[0]['table'] === $this->getPreTable().'leads'
        ) {
            return $this->applyQueryToLeadSegment($queryBuilder, $filter);
        }

        return $this->applyQueryToCompanySegment($queryBuilder, $filter);
    }

    private function applyQueryToCompanySegment(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $companiesTableAlias = $queryBuilder->getTableAlias($this->getPreTable().'companies');
        \assert(is_string($companiesTableAlias));
        $segmentIds = $filter->getParameterValue();

        if (OperatorOptions::EMPTY === $filter->getOperator() || 'notEmpty' === $filter->getOperator()) {
            $dataArray        = $filter->contactSegmentFilterCrate->getArray();
            $currentSegmentId = null;
            if (
                array_key_exists('properties', $dataArray)
                && is_array($dataArray['properties'])
                && array_key_exists('current_company_id', $dataArray['properties'])
            ) {
                $raw = $dataArray['properties']['current_company_id'];
                if (is_int($raw) || (is_string($raw) && ctype_digit($raw)) || is_numeric($raw)) {
                    $currentSegmentId = (int) $raw;
                }
            }

            $sub  = $queryBuilder->createQueryBuilder();
            $t    = $this->generateRandomParameterName();
            $sub->select('1')
                ->from($this->getPreTable().'companies_segments', $t)
                ->where($sub->expr()->eq($t.'.company_id', $companiesTableAlias.'.id'));

            if (null !== $currentSegmentId) {
                $sub->andWhere($sub->expr()->neq($t.'.segment_id', ':current_segment_id'));
                $queryBuilder->setParameter('current_segment_id', $currentSegmentId);
            }

            $expr = (OperatorOptions::EMPTY === $filter->getOperator())
                ? $queryBuilder->expr()->notExists($sub->getSQL())
                : $queryBuilder->expr()->exists($sub->getSQL());

            $queryBuilder->addLogic($expr, $filter->getGlue());

            return $queryBuilder;
        }

        \assert(is_array($segmentIds) || is_numeric($segmentIds));

        if (!is_array($segmentIds)) {
            $segmentIds = [(int) $segmentIds];
        }

        $orLogic = [];
        foreach ($segmentIds as $segmentId) {
            $exclusion = in_array($filter->getOperator(), ['notExists', 'notIn'], true);

            /** @var CompanySegment|null $companySegment */
            $companySegment = $this->entityManager->getRepository(CompanySegment::class)->find($segmentId);
            if (null === $companySegment) {
                throw new SegmentNotFoundException(sprintf('Segment %s used in the filter does not exist anymore.', $this->stringify($segmentId)));
            }

            // Use new getFiltersFromArray() method instead of proxy class
            $filters               = $this->updateCurrentCompanySegmentId($companySegment);
            $contactSegmentFilters = $this->contactSegmentFilterFactory->getFiltersFromArray($filters, []);

            $segmentQueryBuilder           = $this->companySegmentQueryBuilder->assembleCompaniesSegmentQueryBuilder($companySegment, $contactSegmentFilters, true);
            $subSegmentCompaniesTableAlias = $segmentQueryBuilder->getTableAlias($this->getPreTable().'companies');
            \assert(is_string($subSegmentCompaniesTableAlias));
            $segmentQueryBuilder->resetQueryParts(['select'])->select('null');

            if (count($filters) > 0) {
                $segmentQueryBuilder = $this->companySegmentQueryBuilder->addManuallyUnsubscribedQuery($segmentQueryBuilder, $companySegment);
            }

            $segmentQueryBuilder = $this->companySegmentQueryBuilder->addManuallySubscribedQuery($segmentQueryBuilder, $companySegment);
            $segmentQueryBuilder = $this->companySegmentQueryBuilder->addCompanySegmentQuery($segmentQueryBuilder, $companySegment);

            $parameters = $segmentQueryBuilder->getParameters();
            foreach ($parameters as $key => $value) {
                $queryBuilder->setParameter($key, $value);
            }

            $this->companySegmentQueryBuilder->queryBuilderGenerated($companySegment, $segmentQueryBuilder);

            $segmentQueryWherePart = $segmentQueryBuilder->getQueryPart('where');
            $segmentQueryBuilder->where(sprintf('%s.id = %s.id', $companiesTableAlias, $subSegmentCompaniesTableAlias));
            $segmentQueryBuilder->andWhere($segmentQueryWherePart);

            if ($exclusion) {
                $expression = $queryBuilder->expr()->notExists($segmentQueryBuilder->getSQL());
            } else {
                $expression = $queryBuilder->expr()->exists($segmentQueryBuilder->getSQL());
            }

            if (!$exclusion && count($segmentIds) > 1) {
                $orLogic[] = $expression;
            } else {
                $queryBuilder->addLogic($expression, $filter->getGlue());
            }

            // Preserve memory and detach segments that are not needed anymore
            $this->entityManager->detach($companySegment);
        }

        if (count($orLogic) > 0) {
            $queryBuilder->addLogic(new CompositeExpression(CompositeExpression::TYPE_OR, $orLogic), $filter->getGlue());
        }

        return $queryBuilder;
    }

    private function applyQueryToLeadSegment(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $leadAlias               = $queryBuilder->getTableAlias($this->getPreTable().'leads');
        $companiesLeadTableAlias = $this->generateRandomParameterName();
        assert(is_string($leadAlias));
        $queryBuilder->join(
            $leadAlias,
            $this->getPreTable().'companies_leads',
            $companiesLeadTableAlias,
            $companiesLeadTableAlias.'.lead_id = '.$leadAlias.'.id AND '.$companiesLeadTableAlias.'.is_primary = 1'
        );

        $segmentIds = $filter->getParameterValue();
        if (OperatorOptions::EMPTY === $filter->getOperator() || 'notEmpty' === $filter->getOperator()) {
            $segmentIds = $this->entityManager->getRepository(CompanySegment::class)->findAll();
            $segmentIds = array_map(static fn (CompanySegment $segment): ?int => $segment->getId(), $segmentIds);
        }

        \assert(is_array($segmentIds) || is_numeric($segmentIds));

        if (!is_array($segmentIds)) {
            $segmentIds = [(int) $segmentIds];
        }

        $orLogic = [];
        foreach ($segmentIds as $segmentId) {
            $exclusion = in_array($filter->getOperator(), ['notExists', 'notIn', 'empty'], true);

            /** @var CompanySegment|null $companySegment */
            $companySegment = $this->entityManager->getRepository(CompanySegment::class)->find($segmentId);

            if (null === $companySegment) {
                throw new SegmentNotFoundException(sprintf('Segment %s used in the filter does not exist anymore.', $this->stringify($segmentId)));
            }

            $filters               = $this->updateCurrentCompanySegmentId($companySegment);
            $contactSegmentFilters = $this->contactSegmentFilterFactory->getFiltersFromArray($filters, []);

            $segmentQueryBuilder = $this->companySegmentQueryBuilder->assembleCompaniesSegmentQueryBuilderLeadSegment(
                $companySegment,
                $contactSegmentFilters,
                true
            );
            $subSegmentCompaniesTableAlias = $segmentQueryBuilder->getTableAlias($this->getPreTable().'companies');
            if (false === $subSegmentCompaniesTableAlias) {
                $subSegmentCompaniesTableAlias = $this->generateRandomParameterName();
            }
            \assert(is_string($subSegmentCompaniesTableAlias));
            $segmentQueryBuilder->resetQueryParts(['select'])->select('null');

            if (count($filters) > 0) {
                $segmentQueryBuilder = $this->companySegmentQueryBuilder->addManuallyUnsubscribedQuery($segmentQueryBuilder, $companySegment);
            }

            $segmentQueryBuilder = $this->companySegmentQueryBuilder->addManuallySubscribedQuery($segmentQueryBuilder, $companySegment);
            $segmentQueryBuilder = $this->companySegmentQueryBuilder->addCompanySegmentQuery($segmentQueryBuilder, $companySegment);

            $parameters = $segmentQueryBuilder->getParameters();
            foreach ($parameters as $key => $value) {
                $queryBuilder->setParameter($key, $value);
            }

            $this->companySegmentQueryBuilder->queryBuilderGenerated($companySegment, $segmentQueryBuilder);

            $segmentQueryWherePart = $segmentQueryBuilder->getQueryPart('where');

            $segmentQueryBuilder->where(sprintf('%s.company_id = %s.id', $companiesLeadTableAlias, $subSegmentCompaniesTableAlias));
            $segmentQueryBuilder->andWhere($segmentQueryWherePart);
            if ($exclusion) {
                $expression = $queryBuilder->expr()->notExists($segmentQueryBuilder->getSQL());
            } else {
                $expression = $queryBuilder->expr()->exists($segmentQueryBuilder->getSQL());
            }

            if (!$exclusion && count($segmentIds) > 1) {
                $orLogic[] = $expression;
            } else {
                $queryBuilder->addLogic($expression, $filter->getGlue());
            }
            // Preserve memory and detach segments that are not needed anymore
            $this->entityManager->detach($companySegment);
        }

        if (count($orLogic) > 0) {
            $queryBuilder->addLogic(new CompositeExpression(CompositeExpression::TYPE_OR, $orLogic), $filter->getGlue());
        }

        return $queryBuilder;
    }

    /**
     * @return array<array<mixed>>
     */
    private function updateCurrentCompanySegmentId(CompanySegment $companySegment): array
    {
        $filters = $companySegment->getFilters();
        $id      = $companySegment->getId();
        if (null === $id) {
            return $filters;
        }
        foreach ($filters as $index => $filter) {
            if (!is_array($filter)) {
                continue;
            }
            if (array_key_exists('properties', $filter)) {
                $properties = $filter['properties'];
                if (!is_array($properties)) {
                    continue;
                }

                $properties['current_company_id'] = $companySegment->getId();
                $filters[$index]['properties']    = $properties;
            }
        }

        return $filters;
    }

    public function onAddFilter(SegmentDictionaryGenerationEvent $event): void
    {
        $event->addTranslation(CompanySegmentModel::PROPERTIES_FIELD, [
            'type' => self::getServiceId(),
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE => 'onAddFilter',
        ];
    }

    private function getPreTable(): string
    {
        if (is_string(MAUTIC_TABLE_PREFIX)) {
            return MAUTIC_TABLE_PREFIX;
        }

        return '';
    }

    private function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }
        $encoded = @json_encode($value);

        return false !== $encoded ? $encoded : '';
    }
}
