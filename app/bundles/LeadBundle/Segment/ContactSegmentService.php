<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
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
     * @param ContactSegmentFilterFactory $contactSegmentFilterFactory
     * @param ContactSegmentQueryBuilder  $queryBuilder
     * @param Logger                      $logger
     */
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
     * @param array    $batchLimiters
     *
     * @return array
     *
     * @throws Exception\SegmentQueryException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getNewLeadListLeadsCount(LeadList $segment, array $batchLimiters)
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

        $qb = $this->getNewSegmentContactsQuery($segment, $batchLimiters);

        $this->addMinMaxLimiters($qb, $batchLimiters);
        $this->addLeadLimiter($qb, $batchLimiters);

        if (!empty($batchLimiters['excludeVisitors'])) {
            $this->excludeVisitors($qb);
        }

        $qb = $this->contactSegmentQueryBuilder->wrapInCount($qb);

        $this->logger->debug('Segment QB: Create SQL: '.$qb->getDebugOutput(), ['segmentId' => $segment->getId()]);

        $result = $this->timedFetch($qb, $segment->getId());

        return [$segment->getId() => $result];
    }

    /**
     * @param LeadList   $segment
     * @param array|null $batchLimiters for debug purpose only
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getTotalLeadListLeadsCount(LeadList $segment, array $batchLimiters = null)
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

        // Prepend the DISTINCT to the beginning of the select array
        $select = $queryBuilder->getQueryPart('select');
        array_unshift($select, 'DISTINCT l.*');
        $queryBuilder->setQueryPart('select', $select);

        $this->logger->debug('Segment QB: Create Leads SQL: '.$queryBuilder->getDebugOutput(), ['segmentId' => $segment->getId()]);

        $queryBuilder->setMaxResults($limit);

        $this->addMinMaxLimiters($queryBuilder, $batchLimiters);
        $this->addLeadLimiter($queryBuilder, $batchLimiters);

        if (!empty($batchLimiters['dateTime'])) {
            // Only leads in the list at the time of count
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte('l.date_added', $queryBuilder->expr()->literal($batchLimiters['dateTime'])),
                    $queryBuilder->expr()->isNull('l.date_added')
                )
            );
        }

        if (!empty($batchLimiters['excludeVisitors'])) {
            $this->excludeVisitors($queryBuilder);
        }

        $result = $this->timedFetchAll($queryBuilder, $segment->getId());

        return [$segment->getId() => $result];
    }

    /**
     * @param LeadList $segment
     * @param array    $batchLimiters
     *
     * @return array
     *
     * @throws Exception\SegmentQueryException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getOrphanedLeadListLeadsCount(LeadList $segment, array $batchLimiters = [])
    {
        $queryBuilder = $this->getOrphanedLeadListLeadsQueryBuilder($segment, $batchLimiters);
        $queryBuilder = $this->contactSegmentQueryBuilder->wrapInCount($queryBuilder);

        $this->logger->debug('Segment QB: Orphan Leads Count SQL: '.$queryBuilder->getDebugOutput(), ['segmentId' => $segment->getId()]);

        $result = $this->timedFetch($queryBuilder, $segment->getId());

        return [$segment->getId() => $result];
    }

    /**
     * @param LeadList $segment
     * @param array    $batchLimiters
     * @param null|int $limit
     *
     * @return array
     *
     * @throws Exception\SegmentQueryException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getOrphanedLeadListLeads(LeadList $segment, array $batchLimiters = [], $limit = null)
    {
        $queryBuilder = $this->getOrphanedLeadListLeadsQueryBuilder($segment, $batchLimiters, $limit);

        $this->logger->debug('Segment QB: Orphan Leads SQL: '.$queryBuilder->getDebugOutput(), ['segmentId' => $segment->getId()]);

        $result = $this->timedFetchAll($queryBuilder, $segment->getId());

        return [$segment->getId() => $result];
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
        $queryBuilder = $this->contactSegmentQueryBuilder->assembleContactsSegmentQueryBuilder(
            $segment->getId(),
            $this->contactSegmentFilterFactory->getSegmentFilters($segment)
        );

        $queryBuilder = $this->contactSegmentQueryBuilder->addNewContactsRestrictions($queryBuilder, $segment->getId(), $batchLimiters);

        $this->contactSegmentQueryBuilder->queryBuilderGenerated($segment, $queryBuilder);

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
        $segmentFilters = $this->contactSegmentFilterFactory->getSegmentFilters($segment);

        $queryBuilder = $this->contactSegmentQueryBuilder->assembleContactsSegmentQueryBuilder($segment->getId(), $segmentFilters);
        $queryBuilder = $this->contactSegmentQueryBuilder->addManuallySubscribedQuery($queryBuilder, $segment->getId());
        $queryBuilder = $this->contactSegmentQueryBuilder->addManuallyUnsubscribedQuery($queryBuilder, $segment->getId());

        return $queryBuilder;
    }

    /**
     * @param LeadList $segment
     * @param array    $batchLimiters
     * @param null|int $limit
     *
     * @return QueryBuilder
     *
     * @throws Exception\SegmentQueryException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getOrphanedLeadListLeadsQueryBuilder(LeadList $segment, array $batchLimiters = [], $limit = null)
    {
        $segmentFilters = $this->contactSegmentFilterFactory->getSegmentFilters($segment);

        $queryBuilder = $this->contactSegmentQueryBuilder->assembleContactsSegmentQueryBuilder($segment->getId(), $segmentFilters);
        $this->addLeadLimiter($queryBuilder, $batchLimiters);

        $this->contactSegmentQueryBuilder->queryBuilderGenerated($segment, $queryBuilder);

        $qbO = new QueryBuilder($queryBuilder->getConnection());
        $qbO->select('orp.lead_id as id, orp.leadlist_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'orp');
        $qbO->leftJoin('orp', '('.$queryBuilder->getSQL().')', 'members', 'members.id=orp.lead_id');
        $qbO->setParameters($queryBuilder->getParameters());
        $qbO->andWhere($qbO->expr()->eq('orp.leadlist_id', ':orpsegid'));
        $qbO->andWhere($qbO->expr()->isNull('members.id'));
        $qbO->andWhere($qbO->expr()->eq('orp.manually_added', $qbO->expr()->literal(0)));
        $qbO->setParameter(':orpsegid', $segment->getId());
        $this->addLeadLimiter($qbO, $batchLimiters, 'orp.lead_id');

        if ($limit) {
            $qbO->setMaxResults((int) $limit);
        }

        return $qbO;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array        $batchLimiters
     */
    private function addMinMaxLimiters(QueryBuilder $queryBuilder, array $batchLimiters)
    {
        if (!empty($batchLimiters['minId']) && !empty($batchLimiters['maxId'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->comparison('l.id', 'BETWEEN', "{$batchLimiters['minId']} and {$batchLimiters['maxId']}")
            );
        } elseif (!empty($batchLimiters['maxId'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->lte('l.id', $batchLimiters['maxId'])
            );
        } elseif (!empty($batchLimiters['minId'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->gte('l.id', $queryBuilder->expr()->literal((int) $batchLimiters['minId']))
            );
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    private function excludeVisitors(QueryBuilder $queryBuilder)
    {
        $queryBuilder->andWhere($queryBuilder->expr()->isNotNull('l.date_identified'));
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array        $batchLimiters
     * @param string       $leadIdColumn
     */
    private function addLeadLimiter(QueryBuilder $queryBuilder, array $batchLimiters, $leadIdColumn = 'l.id')
    {
        if (empty($batchLimiters['lead_id'])) {
            return;
        }

        $queryBuilder->andWhere($leadIdColumn.' = :leadId')
            ->setParameter('leadId', $batchLimiters['lead_id']);
    }

    /***** DEBUG *****/

    /**
     * Formatting helper.
     *
     * @param $inputSeconds
     *
     * @return string
     */
    private function formatPeriod($inputSeconds)
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
            $start = microtime(true);

            $result = $qb->execute()->fetch(\PDO::FETCH_ASSOC);

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
