<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\LeadListFilteringEvent;
use Mautic\LeadBundle\Event\LeadListQueryBuilderGeneratedEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
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

    /**
     * ContactSegmentQueryBuilder constructor.
     *
     * @param EntityManager            $entityManager
     * @param RandomParameterName      $randomParameterName
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EntityManager $entityManager, RandomParameterName $randomParameterName, EventDispatcherInterface $dispatcher)
    {
        $this->entityManager       = $entityManager;
        $this->randomParameterName = $randomParameterName;
        $this->dispatcher          = $dispatcher;
    }

    /**
     * @param $segmentId
     * @param $segmentFilters
     *
     * @return QueryBuilder
     *
     * @throws SegmentQueryException
     */
    public function assembleContactsSegmentQueryBuilder($segmentId, $segmentFilters)
    {
        /** @var Connection $connection */
        $connection = $this->entityManager->getConnection();
        if ($connection instanceof MasterSlaveConnection) {
            // Prefer a slave connection if available.
            $connection->connect('slave');
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = new QueryBuilder($connection);

        $queryBuilder->select('l.id')->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        /*
         * Validate the plan, check for circular dependencies.
         *
         * the bigger count($plan), the higher complexity of query
         */
        $this->getResolutionPlan($segmentId);

        /** @var ContactSegmentFilter $filter */
        foreach ($segmentFilters as $filter) {
            try {
                $this->dispatchPluginFilteringEvent($filter, $queryBuilder);
            } catch (PluginHandledFilterException $exception) {
                continue;
            }

            $queryBuilder = $filter->applyQuery($queryBuilder);
        }

        $queryBuilder->applyStackLogic();

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $qb
     *
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
        $queryBuilder->setParameters($qb->getParameters());

        return $queryBuilder;
    }

    /**
     * Restrict the query to NEW members of segment.
     *
     * @param QueryBuilder $queryBuilder
     * @param              $segmentId
     * @param              $batchRestrictions
     *
     * @return QueryBuilder
     *
     * @throws QueryException
     */
    public function addNewContactsRestrictions(QueryBuilder $queryBuilder, $segmentId, $batchRestrictions)
    {
        $parts     = $queryBuilder->getQueryParts();
        $setHaving = (count($parts['groupBy']) || !is_null($parts['having']));

        $tableAlias = $this->generateRandomParameterName();
        $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', $tableAlias, $tableAlias.'.lead_id = l.id');
        $queryBuilder->addSelect($tableAlias.'.lead_id AS '.$tableAlias.'_lead_id');

        $expression = $queryBuilder->expr()->eq($tableAlias.'.leadlist_id', $segmentId);

        $queryBuilder->addJoinCondition($tableAlias, $expression);

        if ($setHaving) {
            $restrictionExpression = $queryBuilder->expr()->isNull($tableAlias.'_lead_id');
            $queryBuilder->andHaving($restrictionExpression);
        } else {
            $restrictionExpression = $queryBuilder->expr()->isNull($tableAlias.'.lead_id');
            $queryBuilder->andWhere($restrictionExpression);
        }

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param              $leadListId
     *
     * @return QueryBuilder
     *
     * @throws QueryException
     */
    public function addManuallySubscribedQuery(QueryBuilder $queryBuilder, $leadListId)
    {
        $tableAlias = $this->generateRandomParameterName();

        $existsQueryBuilder = $queryBuilder->getConnection()->createQueryBuilder();

        $existsQueryBuilder
            ->select($tableAlias.'.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', $tableAlias)
            ->andWhere($queryBuilder->expr()->eq($tableAlias.'.leadlist_id', intval($leadListId)))
            ->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq($tableAlias.'.manually_added', 1),
                    $queryBuilder->expr()->eq($tableAlias.'.manually_removed', $queryBuilder->expr()->literal(''))
                )
            );

        $queryBuilder->orWhere(
            $queryBuilder->expr()->in('l.id', $existsQueryBuilder->getSQL())
        );

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param              $leadListId
     *
     * @return QueryBuilder
     *
     * @throws QueryException
     */
    public function addManuallyUnsubscribedQuery(QueryBuilder $queryBuilder, $leadListId)
    {
        $tableAlias = $this->generateRandomParameterName();
        $queryBuilder->leftJoin(
            'l',
            MAUTIC_TABLE_PREFIX.'lead_lists_leads',
            $tableAlias,
            'l.id = '.$tableAlias.'.lead_id and '.$tableAlias.'.leadlist_id = '.intval($leadListId)
        );
        $queryBuilder->addJoinCondition($tableAlias, $queryBuilder->expr()->eq($tableAlias.'.manually_removed', 1));
        $queryBuilder->andWhere($queryBuilder->expr()->isNull($tableAlias.'.lead_id'));

        return $queryBuilder;
    }

    /**
     * @param LeadList     $segment
     * @param QueryBuilder $queryBuilder
     */
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
     * @param ContactSegmentFilter $filter
     * @param QueryBuilder         $queryBuilder
     *
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
                $filterEdges  = $segmentFilter['filter'];
                $segmentEdges = array_merge($segmentEdges, $filterEdges);
            }
        }

        return $segmentEdges;
    }
}
