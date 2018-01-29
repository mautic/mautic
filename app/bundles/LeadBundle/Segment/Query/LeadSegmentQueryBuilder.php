<?php

/*
 * @copyright   2014-2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Query;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Segment\LeadSegmentFilter;
use Mautic\LeadBundle\Segment\LeadSegmentFilters;
use Mautic\LeadBundle\Segment\RandomParameterName;

class LeadSegmentQueryBuilder
{
    /** @var EntityManager */
    private $entityManager;

    /** @var RandomParameterName */
    private $randomParameterName;

    /** @var \Doctrine\DBAL\Schema\AbstractSchemaManager */
    private $schema;

    public function __construct(EntityManager $entityManager, RandomParameterName $randomParameterName)
    {
        $this->entityManager       = $entityManager;
        $this->randomParameterName = $randomParameterName;
        $this->schema              = $this->entityManager->getConnection()->getSchemaManager();
    }

    /**
     * @param                    $id
     * @param LeadSegmentFilters $leadSegmentFilters
     *
     * @return QueryBuilder
     */
    public function getLeadsSegmentQueryBuilder($id, LeadSegmentFilters $leadSegmentFilters)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = new QueryBuilder($this->entityManager->getConnection());

        $queryBuilder->select('*')->from('leads', 'l');

        /** @var LeadSegmentFilter $filter */
        foreach ($leadSegmentFilters as $filter) {
            $queryBuilder = $filter->applyQuery($queryBuilder);
        }

        return $queryBuilder;
    }

    public function wrapInCount(QueryBuilder $qb)
    {
        // Add count functions to the query
        $queryBuilder = new QueryBuilder($this->entityManager->getConnection());
        //  If there is any right join in the query we need to select its it
        $primary = $qb->guessPrimaryLeadIdColumn();

        $qb->addSelect($primary.' as leadIdPrimary');
        $queryBuilder->select('count(leadIdPrimary) count, max(leadIdPrimary) maxId')
                     ->from('('.$qb->getSQL().')', 'sss');
        $queryBuilder->setParameters($qb->getParameters());

        return $queryBuilder;
    }

    public function addNewLeadsRestrictions(QueryBuilder $queryBuilder, $leadListId, $whatever)
    {
        $queryBuilder->select('l.id');

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

    public function addManuallySubscribedQuery(QueryBuilder $queryBuilder, $leadListId)
    {
        $tableAlias = $this->generateRandomParameterName();
        $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', $tableAlias,
                                'l.id = '.$tableAlias.'.lead_id and '.$tableAlias.'.leadlist_id = '.intval($leadListId));
        $queryBuilder->addJoinCondition($tableAlias,
                                        $queryBuilder->expr()->andX(
                                            $queryBuilder->expr()->orX(
                                                $queryBuilder->expr()->isNull($tableAlias.'.manually_removed'),
                                                $queryBuilder->expr()->eq($tableAlias.'.manually_removed', 0)
                                            ),
                                            $queryBuilder->expr()->eq($tableAlias.'.manually_added', 1)
                                        )
        );
        $queryBuilder->andWhere($queryBuilder->expr()->isNotNull($tableAlias.'.lead_id'));

        return $queryBuilder;
    }

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
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @param LeadSegmentFilterDescriptor $translator
     *
     * @return LeadSegmentQueryBuilder
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;

        return $this;
    }

    /**
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @param \Doctrine\DBAL\Schema\AbstractSchemaManager $schema
     *
     * @return LeadSegmentQueryBuilder
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;

        return $this;
    }
}
