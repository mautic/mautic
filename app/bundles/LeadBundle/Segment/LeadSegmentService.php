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
use Mautic\LeadBundle\Entity\LeadListSegmentRepository;
use Mautic\LeadBundle\Segment\Query\LeadSegmentQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Symfony\Bridge\Monolog\Logger;

class LeadSegmentService
{
    /**
     * @var LeadListSegmentRepository
     */
    private $leadListSegmentRepository;

    /**
     * @var LeadSegmentFilterFactory
     */
    private $leadSegmentFilterFactory;

    /**
     * @var LeadSegmentQueryBuilder
     */
    private $leadSegmentQueryBuilder;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var QueryBuilder
     */
    private $preparedQB;

    /**
     * LeadSegmentService constructor.
     *
     * @param LeadSegmentFilterFactory  $leadSegmentFilterFactory
     * @param LeadListSegmentRepository $leadListSegmentRepository
     * @param LeadSegmentQueryBuilder   $queryBuilder
     * @param Logger                    $logger
     */
    public function __construct(
        LeadSegmentFilterFactory $leadSegmentFilterFactory,
        LeadListSegmentRepository $leadListSegmentRepository,
        LeadSegmentQueryBuilder $queryBuilder,
        Logger $logger
    ) {
        $this->leadListSegmentRepository = $leadListSegmentRepository;
        $this->leadSegmentFilterFactory  = $leadSegmentFilterFactory;
        $this->leadSegmentQueryBuilder   = $queryBuilder;
        $this->logger                    = $logger;
    }

    /**
     * @param LeadList $leadList
     * @param          $segmentFilters
     * @param          $batchLimiters
     *
     * @return Query\QueryBuilder|QueryBuilder
     */
    private function getNewLeadListLeadsQuery(LeadList $leadList, $segmentFilters, $batchLimiters)
    {
        if (!is_null($this->preparedQB)) {
            return $this->preparedQB;
        }
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->leadSegmentQueryBuilder->getLeadsSegmentQueryBuilder($leadList->getId(), $segmentFilters);
        $queryBuilder = $this->leadSegmentQueryBuilder->addNewLeadsRestrictions($queryBuilder, $leadList->getId(), $batchLimiters);
        $queryBuilder = $this->leadSegmentQueryBuilder->addManuallySubscribedQuery($queryBuilder, $leadList->getId());
        $queryBuilder = $this->leadSegmentQueryBuilder->addManuallyUnsubsribedQuery($queryBuilder, $leadList->getId());

        return $queryBuilder;
    }

    /**
     * @param LeadList $leadList
     * @param array    $batchLimiters
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getNewLeadListLeadsCount(LeadList $leadList, array $batchLimiters)
    {
        $segmentFilters = $this->leadSegmentFilterFactory->getLeadListFilters($leadList);

        if (!count($segmentFilters)) {
            $this->logger->debug('Segment QB: Segment has no filters', ['segmentId' => $leadList->getId()]);

            return [$leadList->getId() => [
                'count' => '0',
                'maxId' => '0',
            ],
            ];
        }

        $qb = $this->getNewLeadListLeadsQuery($leadList, $segmentFilters, $batchLimiters);
        $qb = $this->leadSegmentQueryBuilder->wrapInCount($qb);

        $this->logger->debug('Segment QB: Create SQL: '.$qb->getDebugOutput(), ['segmentId' => $leadList->getId()]);

        $result = $this->timedFetch($qb, $leadList->getId());

        return [$leadList->getId() => $result];
    }

    /**
     * @param LeadList $leadList
     * @param array    $batchLimiters
     * @param int      $limit
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getNewLeadListLeads(LeadList $leadList, array $batchLimiters, $limit = 1000)
    {
        $segmentFilters = $this->leadSegmentFilterFactory->getLeadListFilters($leadList);

        $qb = $this->getNewLeadListLeadsQuery($leadList, $segmentFilters, $batchLimiters);
        $qb->select('l.*');

        $this->logger->debug('Segment QB: Create Leads SQL: '.$qb->getDebugOutput(), ['segmentId' => $leadList->getId()]);

        $qb->setMaxResults($limit);

        if (!empty($batchLimiters['minId']) && !empty($batchLimiters['maxId'])) {
            $qb->andWhere(
                $qb->expr()->comparison('l.id', 'BETWEEN', "{$batchLimiters['minId']} and {$batchLimiters['maxId']}")
            );
        } elseif (!empty($batchLimiters['maxId'])) {
            $qb->andWhere(
                $qb->expr()->lte('l.id', $batchLimiters['maxId'])
            );
        }

        if (!empty($batchLimiters['dateTime'])) {
            // Only leads in the list at the time of count
            $qb->andWhere(
                $qb->expr()->lte('l.date_added', $qb->expr()->literal($batchLimiters['dateTime']))
            );
        }

        $result = $this->timedFetchAll($qb, $leadList->getId());

        return [$leadList->getId() => $result];
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

            $this->logger->debug('Segment QB: Query took: '.round($end * 100, 2).'ms. Result: '.$qb->getDebugOutput(), ['segmentId' => $segmentId]);
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

            $this->logger->debug('Segment QB: Query took: '.round($end * 100, 2).'ms. Result: '.$qb->getDebugOutput(), ['segmentId' => $segmentId]);
        } catch (\Exception $e) {
            $this->logger->error('Segment QB: Query Exception: '.$e->getMessage(), [
                'query' => $qb->getSQL(), 'parameters' => $qb->getParameters(),
            ]);
            throw $e;
        }

        return $result;
    }
}
