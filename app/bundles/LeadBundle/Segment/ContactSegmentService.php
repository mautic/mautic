<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\Query\ContactSegmentQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Symfony\Bridge\Monolog\Logger;

class ContactSegmentService
{
    /**
     * @var ContactSegmentFilterFactory
     */
    private $contactSegmentFilterFactory;

    /**
     * @var ContactSegmentQueryBuilder
     */
    private $contactSegmentQueryBuilder;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var QueryBuilder
     */
    private $preparedQB;

    public function __construct(
        ContactSegmentFilterFactory $contactSegmentFilterFactory,
        ContactSegmentQueryBuilder $queryBuilder,
        Logger $logger
    ) {
        $this->contactSegmentFilterFactory = $contactSegmentFilterFactory;
        $this->contactSegmentQueryBuilder  = $queryBuilder;
        $this->logger                      = $logger;
    }

    /**
     * @param LeadList $segment
     * @param          $batchLimiters
     *
     * @return QueryBuilder
     *
     * @throws Exception\SegmentQueryException
     * @throws \Exception
     */
    private function getNewSegmentContactsQuery(LeadList $segment, $batchLimiters)
    {
        if (!is_null($this->preparedQB)) {
            return $this->preparedQB;
        }

        $segmentFilters = $this->contactSegmentFilterFactory->getSegmentFilters($segment);

        $queryBuilder = $this->contactSegmentQueryBuilder->assembleContactsSegmentQueryBuilder($segmentFilters);
        $queryBuilder = $this->contactSegmentQueryBuilder->addNewContactsRestrictions($queryBuilder, $segment->getId(), $batchLimiters);
        //$queryBuilder = $this->contactSegmentQueryBuilder->addManuallyUnsubsribedQuery($queryBuilder, $segment->getId());

        return $queryBuilder;
    }

    /**
     * @param LeadList $segment
     *
     * @return QueryBuilder
     *
     * @throws Exception\SegmentQueryException
     * @throws \Exception
     */
    private function getTotalSegmentContactsQuery(LeadList $segment)
    {
        if (!is_null($this->preparedQB)) {
            return $this->preparedQB;
        }

        $segmentFilters = $this->contactSegmentFilterFactory->getSegmentFilters($segment);

        $queryBuilder = $this->contactSegmentQueryBuilder->assembleContactsSegmentQueryBuilder($segmentFilters);
        $queryBuilder = $this->contactSegmentQueryBuilder->addManuallySubscribedQuery($queryBuilder, $segment->getId());
        $queryBuilder = $this->contactSegmentQueryBuilder->addManuallyUnsubscribedQuery($queryBuilder, $segment->getId());

        return $queryBuilder;
    }

    /**
     * @param LeadList $segment
     * @param array    $batchLimiters
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getNewLeadListLeadsCount(LeadList $segment, array $batchLimiters)
    {
        $segmentFilters = $this->contactSegmentFilterFactory->getSegmentFilters($segment);

        if (!count($segmentFilters)) {
            $this->logger->debug('Segment QB: Segment has no filters', ['segmentId' => $segment->getId()]);

            return [$segment->getId() => [
                'count' => '0',
                'maxId' => '0',
            ],
            ];
        }

        $qb = $this->getNewSegmentContactsQuery($segment, $batchLimiters);

        $qb = $this->contactSegmentQueryBuilder->wrapInCount($qb);

        //dump($qb->getLogicStack());

        $this->logger->debug('Segment QB: Create SQL: '.$qb->getDebugOutput(), ['segmentId' => $segment->getId()]);

        $result = $this->timedFetch($qb, $segment->getId());

        return [$segment->getId() => $result];
    }

    /**
     * @param LeadList $segment
     *
     * @return array
     *
     * @throws \Exception
     *
     * @todo This is almost copy of getNewLeadListLeadsCount method. Only difference is that it calls getTotalSegmentContactsQuery
     */
    public function getTotalLeadListLeadsCount(LeadList $segment)
    {
        $segmentFilters = $this->contactSegmentFilterFactory->getSegmentFilters($segment);

        if (!count($segmentFilters)) {
            $this->logger->debug('Segment QB: Segment has no filters', ['segmentId' => $segment->getId()]);

            return [$segment->getId() => [
                'count' => '0',
                'maxId' => '0',
            ],
            ];
        }

        $qb = $this->getTotalSegmentContactsQuery($segment);

        $qb = $this->contactSegmentQueryBuilder->wrapInCount($qb);

        $this->logger->debug('Segment QB: Create SQL: '.$qb->getDebugOutput(), ['segmentId' => $segment->getId()]);
        //dump($qb->getDebugOutput());

        $result = $this->timedFetch($qb, $segment->getId());

        return [$segment->getId() => $result];
    }

    /**
     * @param LeadList $segment
     * @param array    $batchLimiters
     * @param int      $limit
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getNewLeadListLeads(LeadList $segment, array $batchLimiters, $limit = 1000)
    {
        $queryBuilder = $this->getNewSegmentContactsQuery($segment, $batchLimiters);
        $queryBuilder->select('DISTINCT l.id');

        $this->logger->debug('Segment QB: Create Leads SQL: '.$queryBuilder->getDebugOutput(), ['segmentId' => $segment->getId()]);

        $queryBuilder->setMaxResults($limit);

        if (!empty($batchLimiters['minId']) && !empty($batchLimiters['maxId'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->comparison('l.id', 'BETWEEN', "{$batchLimiters['minId']} and {$batchLimiters['maxId']}")
            );
        } elseif (!empty($batchLimiters['maxId'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->lte('l.id', $batchLimiters['maxId'])
            );
        }

        if (!empty($batchLimiters['dateTime'])) {
            // Only leads in the list at the time of count
            $queryBuilder->andWhere(
                $queryBuilder->expr()->lte('l.date_added', $queryBuilder->expr()->literal($batchLimiters['dateTime']))
            );
        }

        $result = $this->timedFetchAll($queryBuilder, $segment->getId());

        return [$segment->getId() => $result];
    }

    /**
     * @param LeadList $segment
     *
     * @return QueryBuilder
     *
     * @throws Exception\SegmentQueryException
     * @throws \Exception
     */
    private function getOrphanedLeadListLeadsQueryBuilder(LeadList $segment)
    {
        $segmentFilters = $this->contactSegmentFilterFactory->getSegmentFilters($segment);

        $queryBuilder = $this->contactSegmentQueryBuilder->assembleContactsSegmentQueryBuilder($segmentFilters);

        $queryBuilder->rightJoin('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'orp', 'l.id = orp.lead_id and orp.leadlist_id = '.$segment->getId());
        $queryBuilder->andWhere($queryBuilder->expr()->andX(
            $queryBuilder->expr()->isNull('l.id'),
            $queryBuilder->expr()->eq('orp.leadlist_id', $segment->getId())
        ));

        $queryBuilder->select($queryBuilder->guessPrimaryLeadContactIdColumn().' as id');

        return $queryBuilder;
    }

    /**
     * @param LeadList $segment
     *
     * @return array
     *
     * @throws Exception\SegmentQueryException
     * @throws \Exception
     */
    public function getOrphanedLeadListLeadsCount(LeadList $segment)
    {
        $queryBuilder = $this->getOrphanedLeadListLeadsQueryBuilder($segment);
        $queryBuilder = $this->contactSegmentQueryBuilder->wrapInCount($queryBuilder);

        $this->logger->debug('Segment QB: Orphan Leads Count SQL: '.$queryBuilder->getDebugOutput(), ['segmentId' => $segment->getId()]);

        $result = $this->timedFetch($queryBuilder, $segment->getId());

        return [$segment->getId() => $result];
    }

    /**
     * @param LeadList $segment
     *
     * @return array
     *
     * @throws Exception\SegmentQueryException
     * @throws \Exception
     */
    public function getOrphanedLeadListLeads(LeadList $segment)
    {
        $queryBuilder = $this->getOrphanedLeadListLeadsQueryBuilder($segment);

        $this->logger->debug('Segment QB: Orphan Leads SQL: '.$queryBuilder->getDebugOutput(), ['segmentId' => $segment->getId()]);

        $result = $this->timedFetchAll($queryBuilder, $segment->getId());

        return [$segment->getId() => $result];
    }

    /**
     * Formatting helper.
     *
     * @param $inputSeconds
     *
     * @return string
     */
    private function format_period($inputSeconds)
    {
        $now = \DateTime::createFromFormat('U.u', number_format($inputSeconds, 6, '.', ''));

        return $now->format('H:i:s.u');
    }

    /**
     * @param QueryBuilder $qb
     * @param int          $segmentId
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function timedFetch(QueryBuilder $qb, $segmentId)
    {
        try {
            $start  = microtime(true);

            $result = $qb->execute()->fetch(\PDO::FETCH_ASSOC);

            $end = microtime(true) - $start;

            $this->logger->debug('Segment QB: Query took: '.$this->format_period($end).', Result count: '.count($result), ['segmentId' => $segmentId]);
        } catch (\Exception $e) {
            $this->logger->error('Segment QB: Query Exception: '.$e->getMessage(), [
                'query' => $qb->getSQL(), 'parameters' => $qb->getParameters(),
            ]);
            throw $e;
        }

        return $result;
    }

    /**
     * @param QueryBuilder $qb
     * @param int          $segmentId
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function timedFetchAll(QueryBuilder $qb, $segmentId)
    {
        try {
            $start  = microtime(true);
            $result = $qb->execute()->fetchAll(\PDO::FETCH_ASSOC);

            $end = microtime(true) - $start;

            $this->logger->debug('Segment QB: Query took: '.$this->format_period($end).'ms. Result count: '.count($result), ['segmentId' => $segmentId]);
        } catch (\Exception $e) {
            $this->logger->error('Segment QB: Query Exception: '.$e->getMessage(), [
                'query' => $qb->getSQL(), 'parameters' => $qb->getParameters(),
            ]);
            throw $e;
        }

        return $result;
    }
}
