<?php

/*
 * @copyright   2014-2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 * @author      Jan Kozak <galvani78@gmail.com>
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Query;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Segment\Exception\SegmentQueryException;
use Mautic\LeadBundle\Segment\LeadSegmentFilter;
use Mautic\LeadBundle\Segment\LeadSegmentFilters;
use Mautic\LeadBundle\Segment\RandomParameterName;

/**
 * Class LeadSegmentQueryBuilder is responsible for building queries for segments.
 *
 * @todo add exceptions
 */
class LeadSegmentQueryBuilder
{
    /** @var EntityManager */
    private $entityManager;

    /** @var RandomParameterName */
    private $randomParameterName;

    /** @var \Doctrine\DBAL\Schema\AbstractSchemaManager */
    private $schema;

    /** @var array */
    private $relatedSegments = [];

    /**
     * LeadSegmentQueryBuilder constructor.
     *
     * @param EntityManager       $entityManager
     * @param RandomParameterName $randomParameterName
     */
    public function __construct(EntityManager $entityManager, RandomParameterName $randomParameterName)
    {
        $this->entityManager       = $entityManager;
        $this->randomParameterName = $randomParameterName;
        $this->schema              = $this->entityManager->getConnection()->getSchemaManager();
    }

    /**
     * @param LeadSegmentFilters $leadSegmentFilters
     * @param null               $backReference
     *
     * @return QueryBuilder
     *
     * @throws SegmentQueryException
     */
    public function assembleContactsSegmentQueryBuilder(LeadSegmentFilters $leadSegmentFilters, $backReference = null)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = new QueryBuilder($this->entityManager->getConnection());

        $queryBuilder->select('l.id')->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $references = [];

        /** @var LeadSegmentFilter $filter */
        foreach ($leadSegmentFilters as $filter) {
            $segmentIdArray = is_array($filter->getParameterValue()) ? $filter->getParameterValue() : [$filter->getParameterValue()];
            //  We will handle references differently than regular segments
            if ($filter->isContactSegmentReference()) {
                if (!is_null($backReference) || in_array($backReference, $this->getContactSegmentRelations($segmentIdArray))) {
                    throw new SegmentQueryException('Circular reference detected.');
                }
                $references = $references + $segmentIdArray;
            }
            $queryBuilder = $filter->applyQuery($queryBuilder);
        }
        $queryBuilder->applyStackLogic();

        return $queryBuilder;
    }

    /**
     * Get the list of segment's related segments.
     *
     * @param $id array
     *
     * @return array
     */
    private function getContactSegmentRelations(array $id)
    {
        $referencedContactSegments = $this->entityManager->getRepository('MauticLeadBundle:LeadList')->findBy(
            ['id' => $id]
        );

        $relations = [];
        foreach ($referencedContactSegments as $segment) {
            $filters = $segment->getFilters();
            foreach ($filters as $filter) {
                if ($filter['field'] == 'leadlist') {
                    $relations[] = $filter['filter'];
                }
            }
        }

        return $relations;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    public function wrapInCount(QueryBuilder $qb)
    {
        // Add count functions to the query
        $queryBuilder = new QueryBuilder($this->entityManager->getConnection());
        //  If there is any right join in the query we need to select its it
        $primary = $qb->guessPrimaryLeadIdColumn();

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

        $queryBuilder->select('count(leadIdPrimary) count, max(leadIdPrimary) maxId')
                     ->from('('.$qb->getSQL().')', 'sss');
        $queryBuilder->setParameters($qb->getParameters());

        return $queryBuilder;
    }

    /**
     * Restrict the query to NEW members of segment.
     *
     * @param QueryBuilder $queryBuilder
     * @param              $leadListId
     * @param              $whatever     @todo document this field
     *
     * @return QueryBuilder
     */
    public function addNewLeadsRestrictions(QueryBuilder $queryBuilder, $leadListId, $whatever)
    {
        //$queryBuilder->select('l.id');

        $parts     = $queryBuilder->getQueryParts();
        $setHaving = (count($parts['groupBy']) || !is_null($parts['having']));

        $tableAlias = $this->generateRandomParameterName();
        $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', $tableAlias, $tableAlias.'.lead_id = l.id');
        $queryBuilder->addSelect($tableAlias.'.lead_id');

        $expression = $queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq($tableAlias.'.leadlist_id', $leadListId),
            $queryBuilder->expr()->lte($tableAlias.'.date_added', "'".$whatever['dateTime']."'")
        );

        $restrictionExpression = $queryBuilder->expr()->isNull($tableAlias.'.lead_id');

        $queryBuilder->addJoinCondition($tableAlias, $expression);

        if ($setHaving) {
            $queryBuilder->andHaving($restrictionExpression);
        } else {
            $queryBuilder->andWhere($restrictionExpression);
        }

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param              $leadListId
     *
     * @return QueryBuilder
     */
    public function addManuallySubscribedQuery(QueryBuilder $queryBuilder, $leadListId)
    {
        $tableAlias = $this->generateRandomParameterName();
        $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', $tableAlias,
                                'l.id = '.$tableAlias.'.lead_id and '.$tableAlias.'.leadlist_id = '.intval($leadListId));
        $queryBuilder->addJoinCondition($tableAlias,
                                        $queryBuilder->expr()->andX(
//                                            $queryBuilder->expr()->orX(
//                                                $queryBuilder->expr()->isNull($tableAlias.'.manually_removed'),
//                                                $queryBuilder->expr()->eq($tableAlias.'.manually_removed', 0)
//                                            ),
                                            $queryBuilder->expr()->eq($tableAlias.'.manually_added', 1)
                                        )
        );
        $queryBuilder->orWhere($queryBuilder->expr()->isNotNull($tableAlias.'.lead_id'));

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param              $leadListId
     *
     * @return QueryBuilder
     */
    public function addManuallyUnsubsribedQuery(QueryBuilder $queryBuilder, $leadListId)
    {
        $tableAlias = $this->generateRandomParameterName();
        $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', $tableAlias,
                                'l.id = '.$tableAlias.'.lead_id and '.$tableAlias.'.leadlist_id = '.intval($leadListId));
        $queryBuilder->addJoinCondition($tableAlias, $queryBuilder->expr()->eq($tableAlias.'.manually_removed', 1));
        $queryBuilder->andWhere($queryBuilder->expr()->isNull($tableAlias.'.lead_id'));

        return $queryBuilder;
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
     * @return LeadSegmentFilterDescriptor
     *
     * @todo Remove this function
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @param LeadSegmentFilterDescriptor $translator
     *
     * @return LeadSegmentQueryBuilder
     *
     * @todo Remove this function
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;

        return $this;
    }

    /**
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     *
     * @todo Remove this function
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @param \Doctrine\DBAL\Schema\AbstractSchemaManager $schema
     *
     * @return LeadSegmentQueryBuilder
     *
     * @todo Remove this function
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;

        return $this;
    }
}
