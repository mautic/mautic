<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Doctrine\DBAL\ArrayParameterType;
use Mautic\LeadBundle\Entity\SegmentCompany;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles Company Segment membership filters within Company Segments and Lead Segments.
 *
 * This filter allows:
 * - Filtering companies by their membership in other company segments (uses pre-materialized company_segments_companies table)
 * - Filtering leads by their primary company's segment membership (uses pre-materialized company_segments_companies table)
 *
 * Performance: Uses simple joins on the materialized company_segments_companies table instead of recursive subqueries.
 * The company_segments_companies table is populated by the UpdateCompanySegmentsCommand.
 */
class CompanySegmentMembershipFilterQueryBuilder extends BaseFilterQueryBuilder implements EventSubscriberInterface
{
    public function __construct(
        RandomParameterName $randomParameterNameService,
        EventDispatcherInterface $dispatcher,
    ) {
        parent::__construct($randomParameterNameService, $dispatcher);
    }

    public static function getServiceId(): string
    {
        return self::class;
    }

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
        $operator   = $filter->getOperator();

        if (OperatorOptions::EMPTY === $operator || 'notEmpty' === $operator) {
            $sub   = $queryBuilder->createQueryBuilder();
            $alias = $this->generateRandomParameterName();

            $sub->select('1')
                ->from($this->getPreTable().SegmentCompany::TABLE_NAME, $alias)
                ->where($sub->expr()->eq($alias.'.company_id', $companiesTableAlias.'.id'))
                ->andWhere($sub->expr()->eq($alias.'.manually_removed', 0));

            $expr = (OperatorOptions::EMPTY === $operator)
                ? $queryBuilder->expr()->notExists($sub->getSQL())
                : $queryBuilder->expr()->exists($sub->getSQL());

            $queryBuilder->addLogic($expr, $filter->getGlue());

            return $queryBuilder;
        }

        // Handle in/notIn/exists/notExists operators with specific segment IDs
        if (!is_array($segmentIds)) {
            $segmentIds = [(int) $segmentIds];
        }

        $isExclusion = in_array($operator, ['notExists', 'notIn'], true);

        $sub       = $queryBuilder->createQueryBuilder();
        $alias     = $this->generateRandomParameterName();
        $paramName = $this->generateRandomParameterName();

        // Simple EXISTS query on materialized company_segments_companies table
        $sub->select('1')
            ->from($this->getPreTable().SegmentCompany::TABLE_NAME, $alias)
            ->where($sub->expr()->eq($alias.'.company_id', $companiesTableAlias.'.id'))
            ->andWhere($sub->expr()->in($alias.'.segment_id', ':'.$paramName))
            ->andWhere($sub->expr()->eq($alias.'.manually_removed', 0));

        $queryBuilder->setParameter($paramName, $segmentIds, ArrayParameterType::INTEGER);

        $expr = $isExclusion
            ? $queryBuilder->expr()->notExists($sub->getSQL())
            : $queryBuilder->expr()->exists($sub->getSQL());

        $queryBuilder->addLogic($expr, $filter->getGlue());

        return $queryBuilder;
    }

    private function applyQueryToLeadSegment(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $leadAlias = $queryBuilder->getTableAlias($this->getPreTable().'leads');
        \assert(is_string($leadAlias));

        $segmentIds = $filter->getParameterValue();
        $operator   = $filter->getOperator();

        // Handle empty/notEmpty operators - check if lead has ANY company in ANY segment
        if (OperatorOptions::EMPTY === $operator || 'notEmpty' === $operator) {
            $sub                  = $queryBuilder->createQueryBuilder();
            $clAlias              = $this->generateRandomParameterName();
            $csAlias              = $this->generateRandomParameterName();
            $isPrimaryParam       = $this->generateRandomParameterName();
            $manuallyRemovedParam = $this->generateRandomParameterName();

            $sub->select('1')
                ->from($this->getPreTable().'companies_leads', $clAlias)
                ->join($clAlias, $this->getPreTable().SegmentCompany::TABLE_NAME, $csAlias, $csAlias.'.company_id = '.$clAlias.'.company_id')
                ->where($sub->expr()->eq($clAlias.'.lead_id', $leadAlias.'.id'))
                ->andWhere($sub->expr()->eq($clAlias.'.is_primary', ':'.$isPrimaryParam))
                ->andWhere($sub->expr()->eq($csAlias.'.manually_removed', ':'.$manuallyRemovedParam));

            $queryBuilder->setParameter($isPrimaryParam, 1);
            $queryBuilder->setParameter($manuallyRemovedParam, 0);

            $expr = (OperatorOptions::EMPTY === $operator)
                ? $queryBuilder->expr()->notExists($sub->getSQL())
                : $queryBuilder->expr()->exists($sub->getSQL());

            $queryBuilder->addLogic($expr, $filter->getGlue());

            return $queryBuilder;
        }

        // Handle in/notIn/exists/notExists operators with specific segment IDs
        if (!is_array($segmentIds)) {
            $segmentIds = [(int) $segmentIds];
        }

        $isExclusion = in_array($operator, ['notExists', 'notIn'], true);

        $sub                  = $queryBuilder->createQueryBuilder();
        $clAlias              = $this->generateRandomParameterName();
        $csAlias              = $this->generateRandomParameterName();
        $segmentIdsParam      = $this->generateRandomParameterName();
        $isPrimaryParam       = $this->generateRandomParameterName();
        $manuallyRemovedParam = $this->generateRandomParameterName();

        // EXISTS query with 2-table JOIN: companies_leads -> company_segments_companies
        $sub->select('1')
            ->from($this->getPreTable().'companies_leads', $clAlias)
            ->join($clAlias, $this->getPreTable().SegmentCompany::TABLE_NAME, $csAlias, $csAlias.'.company_id = '.$clAlias.'.company_id')
            ->where($sub->expr()->eq($clAlias.'.lead_id', $leadAlias.'.id'))
            ->andWhere($sub->expr()->eq($clAlias.'.is_primary', ':'.$isPrimaryParam))
            ->andWhere($sub->expr()->in($csAlias.'.segment_id', ':'.$segmentIdsParam))
            ->andWhere($sub->expr()->eq($csAlias.'.manually_removed', ':'.$manuallyRemovedParam));

        $queryBuilder->setParameter($segmentIdsParam, $segmentIds, ArrayParameterType::INTEGER);
        $queryBuilder->setParameter($isPrimaryParam, 1);
        $queryBuilder->setParameter($manuallyRemovedParam, 0);

        $expr = $isExclusion
            ? $queryBuilder->expr()->notExists($sub->getSQL())
            : $queryBuilder->expr()->exists($sub->getSQL());

        $queryBuilder->addLogic($expr, $filter->getGlue());

        return $queryBuilder;
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
}
