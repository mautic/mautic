<?php

namespace Mautic\LeadBundle\Segment;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\Query\ContactSegmentQueryBuilder;
use Mautic\LeadBundle\Segment\Query\LeadBatchLimiterTrait;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

class ContactSegmentService
{
    use LeadBatchLimiterTrait;

    public function __construct(
        private ContactSegmentFilterFactory $contactSegmentFilterFactory,
        private ContactSegmentQueryBuilder $contactSegmentQueryBuilder,
        private \Psr\Log\LoggerInterface $logger
    ) {
    }

    /**
     * @return array<int,mixed[]>
     *
     * @throws Exception\SegmentQueryException
     * @throws \Doctrine\DBAL\Exception
     */
    public function getNewLeadListLeadsCount(LeadList $segment, array $batchLimiters): array
    {
        $segmentFilters = $this->contactSegmentFilterFactory->getSegmentFilters($segment, $batchLimiters);

        if (!count($segmentFilters)) {
            $this->logger->debug('Segment QB: Segment has no filters', ['segmentId' => $segment->getId()]);

            return [
                $segment->getId() => [
                    'count' => '0',
                    'maxId' => '0',
                ],
            ];
        }

        $qb = $this->getNewSegmentContactsQuery($segment, $batchLimiters);

        $this->addLeadAndMinMaxLimiters($qb, $batchLimiters, 'leads', 'id');

        if (!empty($batchLimiters['excludeVisitors'])) {
            $this->excludeVisitors($qb);
        }

        $qb = $this->contactSegmentQueryBuilder->wrapInCount($qb);

        $this->logger->debug('Segment QB: Create SQL: '.$qb->getDebugOutput(), ['segmentId' => $segment->getId()]);

        $result = $this->timedFetch($qb, $segment->getId());

        return [$segment->getId() => $result];
    }

    /**
     * @param array|null $batchLimiters for debug purpose only
     *
     * @throws \Exception
     */
    public function getTotalLeadListLeadsCount(LeadList $segment, array $batchLimiters = null): array
    {
        $segmentFilters = $this->contactSegmentFilterFactory->getSegmentFilters($segment);

        if (!count($segmentFilters)) {
            $this->logger->debug('Segment QB: Segment has no filters', ['segmentId' => $segment->getId()]);

            return [
                $segment->getId() => [
                    'count' => '0',
                    'maxId' => '0',
                ],
            ];
        }

        $qb = $this->getTotalSegmentContactsQuery($segment);

        if (!empty($batchLimiters['excludeVisitors'])) {
            $this->excludeVisitors($qb);
        }

        $qb = $this->contactSegmentQueryBuilder->wrapInCount($qb);

        $this->logger->debug('Segment QB: Create SQL: '.$qb->getDebugOutput(), ['segmentId' => $segment->getId()]);

        $result = $this->timedFetch($qb, $segment->getId());

        return [$segment->getId() => $result];
    }

    /**
     * @param int $limit
     *
     * @return array<int,mixed[]>
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception\SegmentQueryException
     */
    public function getNewLeadListLeads(LeadList $segment, array $batchLimiters, $limit = 1000): array
    {
        $queryBuilder = $this->getNewLeadListLeadsQueryBuilder($segment, $batchLimiters);
        $queryBuilder->setMaxResults($limit);

        $result = $this->timedFetchAll($queryBuilder, $segment->getId());

        return [$segment->getId() => $result];
    }

    /**
     * @param mixed[] $batchLimiters
     */
    public function getNewLeadListLeadsQueryBuilder(LeadList $segment, array $batchLimiters, bool $addNewContactsRestrictions = true): QueryBuilder
    {
        $queryBuilder    = $this->getNewSegmentContactsQuery($segment, $batchLimiters, $addNewContactsRestrictions);
        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');

        // Prepend the DISTINCT to the beginning of the select array
        $select = $queryBuilder->getQueryPart('select');

        // We are removing it because we will have to add it later
        // to make sure it's the first column in the query
        $key = array_search($leadsTableAlias.'.id', $select);
        if (false !== $key) {
            unset($select[$key]);
        }

        // We only need to use distinct if we join other tables to the leads table
        $join     = $queryBuilder->getQueryPart('join');
        $distinct = is_array($join) && (0 < count($join)) ? 'DISTINCT ' : '';
        // Make sure that leads.id is the first column
        array_unshift($select, $distinct.$leadsTableAlias.'.id');
        $queryBuilder->resetQueryPart('select');
        $queryBuilder->select($select);

        $this->logger->debug('Segment QB: Create Leads SQL: '.$queryBuilder->getDebugOutput(), ['segmentId' => $segment->getId()]);

        $this->addLeadAndMinMaxLimiters($queryBuilder, $batchLimiters, 'leads', 'id');

        if (!empty($batchLimiters['dateTime'])) {
            // Only leads in the list at the time of count
            $queryBuilder->andWhere(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->lte($leadsTableAlias.'.date_added', $queryBuilder->expr()->literal($batchLimiters['dateTime'])),
                    $queryBuilder->expr()->isNull($leadsTableAlias.'.date_added')
                )
            );
        }

        if (!empty($batchLimiters['excludeVisitors'])) {
            $this->excludeVisitors($queryBuilder);
        }

        return $queryBuilder;
    }

    /**
     * @throws Exception\SegmentQueryException
     * @throws \Doctrine\DBAL\Exception
     */
    public function getOrphanedLeadListLeadsCount(LeadList $segment, array $batchLimiters = []): array
    {
        $queryBuilder = $this->getOrphanedLeadListLeadsQueryBuilder($segment, $batchLimiters);
        $queryBuilder = $this->contactSegmentQueryBuilder->wrapInCount($queryBuilder);

        $this->logger->debug('Segment QB: Orphan Leads Count SQL: '.$queryBuilder->getDebugOutput(), ['segmentId' => $segment->getId()]);

        $result = $this->timedFetch($queryBuilder, $segment->getId());

        return [$segment->getId() => $result];
    }

    /**
     * @param int|null $limit
     *
     * @throws Exception\SegmentQueryException
     * @throws \Doctrine\DBAL\Exception
     */
    public function getOrphanedLeadListLeads(LeadList $segment, array $batchLimiters = [], $limit = null): array
    {
        $queryBuilder = $this->getOrphanedLeadListLeadsQueryBuilder($segment, $batchLimiters, $limit);

        $this->logger->debug('Segment QB: Orphan Leads SQL: '.$queryBuilder->getDebugOutput(), ['segmentId' => $segment->getId()]);

        $result = $this->timedFetchAll($queryBuilder, $segment->getId());

        return [$segment->getId() => $result];
    }

    /**
     * @param array<string, mixed> $batchLimiters
     *
     * @throws Exception\SegmentQueryException
     * @throws \Exception
     */
    private function getNewSegmentContactsQuery(LeadList $segment, array $batchLimiters = [], bool $addNewContactsRestrictions = true): QueryBuilder
    {
        $queryBuilder = $this->contactSegmentQueryBuilder->assembleContactsSegmentQueryBuilder(
            $segment->getId(),
            $this->contactSegmentFilterFactory->getSegmentFilters($segment, $batchLimiters)
        );

        if ($addNewContactsRestrictions) {
            $queryBuilder = $this->contactSegmentQueryBuilder->addNewContactsRestrictions($queryBuilder, (int) $segment->getId(), $batchLimiters);
        }

        $this->contactSegmentQueryBuilder->queryBuilderGenerated($segment, $queryBuilder);

        return $queryBuilder;
    }

    /**
     * @throws Exception\SegmentQueryException
     * @throws \Exception
     */
    private function getTotalSegmentContactsQuery(LeadList $segment): QueryBuilder
    {
        $segmentFilters = $this->contactSegmentFilterFactory->getSegmentFilters($segment);

        $queryBuilder = $this->contactSegmentQueryBuilder->assembleContactsSegmentQueryBuilder($segment->getId(), $segmentFilters);
        $queryBuilder = $this->contactSegmentQueryBuilder->addManuallySubscribedQuery($queryBuilder, $segment->getId());

        return $this->contactSegmentQueryBuilder->addManuallyUnsubscribedQuery($queryBuilder, $segment->getId());
    }

    /**
     * @param int|null $limit
     *
     * @return QueryBuilder
     *
     * @throws Exception\SegmentQueryException
     * @throws \Doctrine\DBAL\Exception
     */
    public function getOrphanedLeadListLeadsQueryBuilder(LeadList $segment, array $batchLimiters = [], $limit = null)
    {
        $segmentFilters = $this->contactSegmentFilterFactory->getSegmentFilters($segment, $batchLimiters);

        $queryBuilder = $this->contactSegmentQueryBuilder->assembleContactsSegmentQueryBuilder($segment->getId(), $segmentFilters);

        $this->addLeadAndMinMaxLimiters($queryBuilder, $batchLimiters, 'leads', 'id');

        $this->contactSegmentQueryBuilder->queryBuilderGenerated($segment, $queryBuilder);

        $expr = $queryBuilder->expr();
        $qbO  = $queryBuilder->createQueryBuilder();
        $qbO->select('orp.lead_id as id, orp.leadlist_id');
        $qbO->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'orp');
        $qbO->setParameters($queryBuilder->getParameters(), $queryBuilder->getParameterTypes());
        $qbO->andWhere($expr->eq('orp.leadlist_id', ':orpsegid'));
        $qbO->andWhere($expr->eq('orp.manually_added', $expr->literal(0)));
        $qbO->andWhere($expr->notIn('orp.lead_id', $queryBuilder->getSQL()));
        $qbO->setParameter('orpsegid', $segment->getId());
        $this->addLeadAndMinMaxLimiters($qbO, $batchLimiters, 'lead_lists_leads');

        if ($limit) {
            $qbO->setMaxResults((int) $limit);
        }

        return $qbO;
    }

    private function excludeVisitors(QueryBuilder $queryBuilder): void
    {
        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $queryBuilder->andWhere($queryBuilder->expr()->isNotNull($leadsTableAlias.'.date_identified'));
    }

    /***** DEBUG *****/

    /**
     * Formatting helper.
     *
     * @return string
     */
    private function formatPeriod($inputSeconds)
    {
        $now = \DateTime::createFromFormat('U.u', number_format($inputSeconds, 6, '.', ''));

        return $now->format('H:i:s.u');
    }

    /**
     * @param int $segmentId
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function timedFetch(QueryBuilder $qb, $segmentId)
    {
        try {
            $start = microtime(true);

            $result = $qb->executeQuery()->fetchAssociative();

            $end = microtime(true) - $start;

            $this->logger->debug('Segment QB: Query took: '.$this->formatPeriod($end).', Result count: '.count($result), ['segmentId' => $segmentId]);
        } catch (\Exception $e) {
            $this->logger->error(
                'Segment QB: Query Exception: '.$e->getMessage(),
                [
                    'query'      => $qb->getSQL(),
                    'parameters' => $qb->getParameters(),
                ]
            );
            throw $e;
        }

        return $result;
    }

    /**
     * @param int $segmentId
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function timedFetchAll(QueryBuilder $qb, $segmentId)
    {
        try {
            $start  = microtime(true);
            $result = $qb->executeQuery()->fetchAllAssociative();

            $end = microtime(true) - $start;

            $this->logger->debug(
                'Segment QB: Query took: '.$this->formatPeriod($end).'ms. Result count: '.count($result),
                ['segmentId' => $segmentId]
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Segment QB: Query Exception: '.$e->getMessage(),
                [
                    'query'      => $qb->getSQL(),
                    'parameters' => $qb->getParameters(),
                ]
            );
            throw $e;
        }

        return $result;
    }
}
