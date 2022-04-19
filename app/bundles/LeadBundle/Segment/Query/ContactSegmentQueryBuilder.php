<?php

namespace Mautic\LeadBundle\Segment\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
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
 * Class ContactSegmentQueryBuilder is responsible for building queries for segments.
 */
class ContactSegmentQueryBuilder
{
    /** @var EntityManager */
    private $entityManager;

    /** @var RandomParameterName */
    private $randomParameterName;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var array Contains segment edges mapping */
    private $dependencyMap = [];

    public function __construct(EntityManager $entityManager, RandomParameterName $randomParameterName, EventDispatcherInterface $dispatcher)
    {
        $this->entityManager       = $entityManager;
        $this->randomParameterName = $randomParameterName;
        $this->dispatcher          = $dispatcher;
    }

    /**
     * @param int                   $segmentId
     * @param ContactSegmentFilters $segmentFilters
     *
     * @return QueryBuilder
     *
     * @throws SegmentQueryException
     */
    public function assembleContactsSegmentQueryBuilder($segmentId, $segmentFilters, bool $changeAlias = false)
    {
        /** @var Connection $connection */
        $connection = $this->entityManager->getConnection();
        if ($connection instanceof MasterSlaveConnection) {
            // Prefer a slave connection if available.
            $connection->connect('slave');
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
            } catch (PluginHandledFilterException $exception) {
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
     * @return QueryBuilder
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function wrapInCount(QueryBuilder $qb)
    {
        /** @var Connection $connection */
        $connection = $this->entityManager->getConnection();
        if ($connection instanceof MasterSlaveConnection) {
            // Prefer a slave connection if available.
            $connection->connect('slave');
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
     * @param int $segmentId
     *
     * @return QueryBuilder
     *
     * @throws QueryException
     */
    public function addNewContactsRestrictions(QueryBuilder $queryBuilder, $segmentId)
    {
        $leadsTableAlias    = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $expr               = $queryBuilder->expr();
        $tableAlias         = $this->generateRandomParameterName();
        $segmentIdParameter = ":{$tableAlias}segmentId";

        $segmentQueryBuilder = $queryBuilder->createQueryBuilder()
            ->select($tableAlias.'.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', $tableAlias)
            ->andWhere($expr->eq($tableAlias.'.leadlist_id', $segmentIdParameter));

        $queryBuilder->setParameter($segmentIdParameter, $segmentId);
        $queryBuilder->andWhere($expr->notIn($leadsTableAlias.'.id', $segmentQueryBuilder->getSQL()));

        return $queryBuilder;
    }

    /**
     * @param int $leadListId
     *
     * @return QueryBuilder
     */
    public function addManuallySubscribedQuery(QueryBuilder $queryBuilder, $leadListId)
    {
        $leadsTableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $tableAlias      = $this->generateRandomParameterName();

        $existsQueryBuilder = $queryBuilder->getConnection()->createQueryBuilder();

        $existsQueryBuilder
            ->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', $tableAlias)
            ->andWhere($queryBuilder->expr()->eq($tableAlias.'.leadlist_id', intval($leadListId)))
            ->andWhere(
                $queryBuilder->expr()->orX(
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
     * @param int $leadListId
     *
     * @return QueryBuilder
     *
     * @throws QueryException
     */
    public function addManuallyUnsubscribedQuery(QueryBuilder $queryBuilder, $leadListId)
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

    public function queryBuilderGenerated(LeadList $segment, QueryBuilder $queryBuilder)
    {
        if (!$this->dispatcher->hasListeners(LeadEvents::LIST_FILTERS_QUERYBUILDER_GENERATED)) {
            return;
        }

        $event = new LeadListQueryBuilderGeneratedEvent($segment, $queryBuilder);
        $this->dispatcher->dispatch(LeadEvents::LIST_FILTERS_QUERYBUILDER_GENERATED, $event);
    }

    /**
     * Generate a unique parameter name.
     *
     * @return string
     */
    private function generateRandomParameterName()
    {
        return $this->randomParameterName->generateRandomParameterName();
    }

    /**
     * @throws PluginHandledFilterException
     */
    private function dispatchPluginFilteringEvent(ContactSegmentFilter $filter, QueryBuilder $queryBuilder)
    {
        if ($this->dispatcher->hasListeners(LeadEvents::LIST_FILTERS_ON_FILTERING)) {
            //  This has to run for every filter
            $filterCrate = $filter->contactSegmentFilterCrate->getArray();

            $alias = $this->generateRandomParameterName();
            $event = new LeadListFilteringEvent($filterCrate, null, $alias, $filterCrate['operator'], $queryBuilder, $this->entityManager);
            $this->dispatcher->dispatch(LeadEvents::LIST_FILTERS_ON_FILTERING, $event);
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
     *
     * @return array
     */
    private function getSegmentEdges($segmentId)
    {
        $segment = $this->entityManager->getRepository('MauticLeadBundle:LeadList')->find($segmentId);
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
