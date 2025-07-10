<?php

namespace Mautic\LeadBundle\Segment\Query;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\LeadListFilteringEvent;
use Mautic\LeadBundle\Event\LeadListQueryBuilderGeneratedEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\ContactSegmentFilters;
use Mautic\LeadBundle\Segment\Exception\PluginHandledFilterException;
use Mautic\LeadBundle\Segment\Exception\SegmentQueryException;
use Mautic\LeadBundle\Segment\RandomParameterName;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Responsible for building queries for segments.
 */
class ContactSegmentQueryBuilder
{
    use LeadBatchLimiterTrait;

    /**
     * @var array Contains segment edges mapping
     */
    private array $dependencyMap = [];

    public function __construct(
        private EntityManager $entityManager,
        private RandomParameterName $randomParameterName,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @param int                   $segmentId
     * @param ContactSegmentFilters $segmentFilters
     *
     * @throws SegmentQueryException
     */
    public function assembleContactsSegmentQueryBuilder($segmentId, $segmentFilters, bool $changeAlias = false): QueryBuilder
    {
        /** @var Connection $connection */
        $connection = $this->entityManager->getConnection();
        if ($connection instanceof \Doctrine\DBAL\Connections\PrimaryReadReplicaConnection) {
            // Prefer a replica connection if available.
            $connection->ensureConnectedToReplica();
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = new QueryBuilder($connection);

        $leadsTableAlias = $changeAlias ? $this->generateRandomParameterName() : Lead::DEFAULT_ALIAS;

        $queryBuilder->select($leadsTableAlias.'.id')->from(MAUTIC_TABLE_PREFIX.'leads', $leadsTableAlias);

        /*
         * Validate the plan, check for circular dependencies.
         *
         * the bigger count($plan), the higher complexity of query
         */
        $this->getResolutionPlan($segmentId);

        $params     = $queryBuilder->getParameters();
        $paramTypes = $queryBuilder->getParameterTypes();

        /** @var ContactSegmentFilter $filter */
        foreach ($segmentFilters as $filter) {
            try {
                $this->dispatchPluginFilteringEvent($filter, $queryBuilder);
            } catch (PluginHandledFilterException) {
                continue;
            }

            $queryBuilder = $filter->applyQuery($queryBuilder);
            // We need to collect params between union queries in this iteration,
            // because they are overwritten by new union query build
            $params     = array_merge($params, $queryBuilder->getParameters());
            $paramTypes = array_merge($paramTypes, $queryBuilder->getParameterTypes());
        }

        $queryBuilder->setParameters($params, $paramTypes);
        $queryBuilder->applyStackLogic();

        return $queryBuilder;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function wrapInCount(QueryBuilder $qb): QueryBuilder
    {
        /** @var Connection $connection */
        $connection = $this->entityManager->getConnection();
        if ($connection instanceof \Doctrine\DBAL\Connections\PrimaryReadReplicaConnection) {
            // Prefer a replica connection if available.
            $connection->ensureConnectedToReplica();
        }

        // Add count functions to the query
        $queryBuilder = new QueryBuilder($connection);

        //  If there is any right join in the query we need to select its it
        $primary = $qb->guessPrimaryLeadContactIdColumn();

        $currentSelects = [];
        foreach ($qb->getQueryParts()['select'] as $select) {
            if ($select != $primary) {
                $currentSelects[] = $select;
            }
        }

        $qb->select('DISTINCT '.$primary.' as leadIdPrimary');
        foreach ($currentSelects as $select) {
            $qb->addSelect($select);
        }

        $queryBuilder->select('count(leadIdPrimary) count, max(leadIdPrimary) maxId, min(leadIdPrimary) minId')
            ->from('('.$qb->getSQL().')', 'sss');

        $queryBuilder->setParameters($qb->getParameters(), $qb->getParameterTypes());

        return $queryBuilder;
    }

    /**
     * Restrict the query to NEW members of segment.
     *
     * @param array<string, mixed> $batchLimiters
     *
     * @throws QueryException
     */
    public function addNewContactsRestrictions(QueryBuilder $queryBuilder, int $segmentId, array $batchLimiters = []): QueryBuilder
    {
        $leadsTableAlias    = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $expr               = $queryBuilder->expr();
        $tableAlias         = $this->generateRandomParameterName();
        $segmentIdParameter = ":{$tableAlias}segmentId";

        $segmentQueryBuilder = $queryBuilder->createQueryBuilder()
            ->select($tableAlias.'.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', $tableAlias)
            ->andWhere($expr->eq($tableAlias.'.leadlist_id', $segmentIdParameter));

        $queryBuilder->setParameter("{$tableAlias}segmentId", $segmentId);

        $this->addLeadAndMinMaxLimiters($segmentQueryBuilder, $batchLimiters, 'lead_lists_leads');

        $queryBuilder->andWhere($expr->notIn($leadsTableAlias.'.id', $segmentQueryBuilder->getSQL()));

        return $queryBuilder;
    }

    public function addManuallySubscribedQuery(QueryBuilder $queryBuilder, int $leadListId): QueryBuilder
    {
        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $tableAlias      = $this->generateRandomParameterName();

        $existsQueryBuilder = $queryBuilder->createQueryBuilder();

        $existsQueryBuilder
            ->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', $tableAlias)
            ->andWhere($queryBuilder->expr()->eq($tableAlias.'.leadlist_id', intval($leadListId)))
            ->andWhere(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq($tableAlias.'.manually_added', 1),
                    $queryBuilder->expr()->eq($tableAlias.'.manually_removed', $queryBuilder->expr()->literal(''))
                )
            );

        $existingQueryWherePart = $existsQueryBuilder->getQueryPart('where');
        $existsQueryBuilder->where("$leadsTableAlias.id = $tableAlias.lead_id");
        $existsQueryBuilder->andWhere($existingQueryWherePart);

        $queryBuilder->orWhere(
            $queryBuilder->expr()->exists($existsQueryBuilder->getSQL())
        );

        return $queryBuilder;
    }

    /**
     * @throws QueryException
     */
    public function addManuallyUnsubscribedQuery(QueryBuilder $queryBuilder, int $leadListId): QueryBuilder
    {
        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $tableAlias      = $this->generateRandomParameterName();
        $queryBuilder->leftJoin(
            $leadsTableAlias,
            MAUTIC_TABLE_PREFIX.'lead_lists_leads',
            $tableAlias,
            $leadsTableAlias.'.id = '.$tableAlias.'.lead_id and '.$tableAlias.'.leadlist_id = '.intval($leadListId)
        );
        $queryBuilder->addJoinCondition($tableAlias, $queryBuilder->expr()->eq($tableAlias.'.manually_removed', 1));
        $queryBuilder->andWhere($queryBuilder->expr()->isNull($tableAlias.'.lead_id'));

        return $queryBuilder;
    }

    public function queryBuilderGenerated(LeadList $segment, QueryBuilder $queryBuilder): void
    {
        if (!$this->dispatcher->hasListeners(LeadEvents::LIST_FILTERS_QUERYBUILDER_GENERATED)) {
            return;
        }

        $event = new LeadListQueryBuilderGeneratedEvent($segment, $queryBuilder);
        $this->dispatcher->dispatch($event, LeadEvents::LIST_FILTERS_QUERYBUILDER_GENERATED);
    }

    /**
     * Generate a unique parameter name.
     */
    private function generateRandomParameterName(): string
    {
        return $this->randomParameterName->generateRandomParameterName();
    }

    /**
     * @throws PluginHandledFilterException
     */
    private function dispatchPluginFilteringEvent(ContactSegmentFilter $filter, QueryBuilder $queryBuilder): void
    {
        if ($this->dispatcher->hasListeners(LeadEvents::LIST_FILTERS_ON_FILTERING)) {
            //  This has to run for every filter
            $filterCrate = $filter->contactSegmentFilterCrate->getArray();

            $alias = $this->generateRandomParameterName();
            $event = new LeadListFilteringEvent($filterCrate, null, $alias, $filterCrate['operator'], $queryBuilder, $this->entityManager);
            $this->dispatcher->dispatch($event, LeadEvents::LIST_FILTERS_ON_FILTERING);
            if ($event->isFilteringDone()) {
                $queryBuilder->addLogic($event->getSubQuery(), $filter->getGlue());

                throw new PluginHandledFilterException();
            }
        }
    }

    /**
     * Returns array with plan for processing.
     *
     * @param int   $segmentId
     * @param array $seen
     * @param array $resolved
     *
     * @return array
     *
     * @throws SegmentQueryException
     */
    private function getResolutionPlan($segmentId, $seen = [], &$resolved = [])
    {
        $seen[] = $segmentId;

        if (!isset($this->dependencyMap[$segmentId])) {
            $this->dependencyMap[$segmentId] = $this->getSegmentEdges($segmentId);
        }

        $edges = $this->dependencyMap[$segmentId];

        foreach ($edges as $edge) {
            if (!in_array($edge, $resolved)) {
                if (in_array($edge, $seen)) {
                    throw new SegmentQueryException('Circular reference detected.');
                }
                $this->getResolutionPlan($edge, $seen, $resolved);
            }
        }

        $resolved[] = $segmentId;

        return $resolved;
    }

    /**
     * @param int $segmentId
     */
    private function getSegmentEdges($segmentId): array
    {
        $segment = $this->entityManager->getRepository(LeadList::class)->find($segmentId);
        if (null === $segment) {
            return [];
        }

        $segmentFilters = $segment->getFilters();
        $segmentEdges   = [];

        foreach ($segmentFilters as $segmentFilter) {
            if (isset($segmentFilter['field']) && 'leadlist' === $segmentFilter['field']) {
                $bcFilter     = $segmentFilter['filter'] ?? [];
                $filterEdges  = $segmentFilter['properties']['filter'] ?? $bcFilter;
                $segmentEdges = array_merge($segmentEdges, $filterEdges);
            }
        }

        return $segmentEdges;
    }
}
