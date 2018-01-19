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

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListSegmentRepository;
use Mautic\LeadBundle\Services\LeadSegmentFilterQueryBuilderTrait;
use Mautic\LeadBundle\Services\LeadSegmentQueryBuilder;

class LeadSegmentService
{
    use LeadSegmentFilterQueryBuilderTrait;

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
    private $queryBuilder;

    public function __construct(
        LeadSegmentFilterFactory $leadSegmentFilterFactory,
        LeadListSegmentRepository $leadListSegmentRepository,
        LeadSegmentQueryBuilder $queryBuilder)
    {
        $this->leadListSegmentRepository = $leadListSegmentRepository;
        $this->leadSegmentFilterFactory  = $leadSegmentFilterFactory;
        $this->queryBuilder              = $queryBuilder;
    }

    public function getDqlWithParams(Doctrine_Query $query)
    {
        $vals = $query->getFlattenedParams();
        $sql  = $query->getDql();
        $sql  = str_replace('?', '%s', $sql);

        return vsprintf($sql, $vals);
    }

    public function getNewLeadsByListCount(LeadList $entity, array $batchLimiters)
    {
        $segmentFilters = $this->leadSegmentFilterFactory->getLeadListFilters($entity);

        echo '<hr/>New version result:';
        $versionStart = microtime(true);

        if (!count($segmentFilters)) {
            return [0];
        }
        /** @var QueryBuilder $qb */
        $qb = $this->queryBuilder->getLeadsSegmentQueryBuilder($entity->getId(), $segmentFilters);
        $qb = $this->queryBuilder->addNewLeadsRestrictions($qb, $entity->getId(), $batchLimiters);
        $qb = $this->queryBuilder->addManuallySubscribedQuery($qb, $entity->getId());
        $qb = $this->queryBuilder->addManuallyUnsubsribedQuery($qb, $entity->getId());
        $qb = $this->queryBuilder->wrapInCount($qb);

        //  Debug output
        $sql = $qb->getSQL();
        foreach ($qb->getParameters() as $k=>$v) {
            $sql = str_replace(":$k", "'$v'", $sql);
        }

        echo '<hr/>';
        dump($sql);
        try {
            $start = microtime(true);

            $stmt    = $qb->execute();
            $results = $stmt->fetchAll();

            $end = microtime(true) - $start;
            dump('Query took '.(1000 * $end).'ms');

            $start = microtime(true);

            $result = $qb->execute()->fetch();

            $versionEnd = microtime(true) - $versionStart;
            dump('Total query assembly took:'.(1000 * $versionEnd).'ms');

            dump($result);
        } catch (\Exception $e) {
            dump('Query exception: '.$e->getMessage());
        }

        return $result;
    }
}
