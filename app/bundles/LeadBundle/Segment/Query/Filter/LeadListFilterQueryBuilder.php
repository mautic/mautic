<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Segment\LeadSegmentFilter;
use Mautic\LeadBundle\Segment\LeadSegmentFilterFactory;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use Mautic\LeadBundle\Services\LeadSegmentQueryBuilder;

/**
 * Class LeadListFilterQueryBuilder.
 */
class LeadListFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /**
     * @var LeadSegmentQueryBuilder
     */
    private $leadSegmentQueryBuilder;

    /**
     * @var LeadSegmentFilterFactory
     */
    private $leadSegmentFilterFactory;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * LeadListFilterQueryBuilder constructor.
     *
     * @param RandomParameterName      $randomParameterNameService
     * @param LeadSegmentQueryBuilder  $leadSegmentQueryBuilder
     * @param EntityManager            $entityManager
     * @param LeadSegmentFilterFactory $leadSegmentFilterFactory
     */
    public function __construct(
        RandomParameterName $randomParameterNameService,
        LeadSegmentQueryBuilder $leadSegmentQueryBuilder,
        EntityManager $entityManager,
        LeadSegmentFilterFactory $leadSegmentFilterFactory
    ) {
        parent::__construct($randomParameterNameService);

        $this->leadSegmentQueryBuilder  = $leadSegmentQueryBuilder;
        $this->leadSegmentFilterFactory = $leadSegmentFilterFactory;
        $this->entityManager            = $entityManager;
    }

    /**
     * @return string
     */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.special.leadlist';
    }

    /**
     * @param QueryBuilder      $queryBuilder
     * @param LeadSegmentFilter $filter
     *
     * @return QueryBuilder
     */
    public function applyQuery(QueryBuilder $queryBuilder, LeadSegmentFilter $filter)
    {
        $segmentIds = $filter->getParameterValue();

        if (!is_array($segmentIds)) {
            $segmentIds = [intval($segmentIds)];
        }

        $leftIds = [];
        $innerIds = [];

        foreach ($segmentIds as $segmentId) {
            $ids[]     = $segmentId;
            $exclusion = ($filter->getOperator() == 'exists');
            if ($exclusion) {
                $leftIds[] = $segmentId;
            } else {
                if (!isset($innerAlias)) {
                    $innerAlias = $this->generateRandomParameterName();
                }
                $innerIds[] = $segmentId;
            }
        }

        if (count($leftIds)) {
            $leftAlias = $this->generateRandomParameterName();
            $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', $leftAlias,
                                    $queryBuilder->expr()->andX(
                                        $queryBuilder->expr()->in('l.id', $leftIds),
                                        $queryBuilder->expr()->eq('l.id', $leftAlias.'.lead_id'))
            );
            $queryBuilder->andWhere($queryBuilder->expr()->isNull($leftAlias.'.lead_id'));
        }

        if (count($innerIds)) {
            $leftAlias = $this->generateRandomParameterName();
            $queryBuilder->innerJoin('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', $leftAlias,
                                    $queryBuilder->expr()->andX(
                                        $queryBuilder->expr()->in('l.id', $innerIds),
                                        $queryBuilder->expr()->eq('l.id', $leftAlias.'.lead_id'))
            );
        }

        $queryBuilder->innerJoin('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', $innerAlias, 'l.id = '.$innerAlias.'.lead_id');

        return $queryBuilder;
    }
}
