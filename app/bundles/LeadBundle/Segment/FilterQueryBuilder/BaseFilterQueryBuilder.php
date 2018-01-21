<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\FilterQueryBuilder;

use Mautic\LeadBundle\Segment\LeadSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;

/**
 * Class BaseFilterQueryBuilder.
 */
class BaseFilterQueryBuilder implements FilterQueryBuilderInterface
{
    /** @var RandomParameterName */
    private $parameterNameGenerator;

    /**
     * BaseFilterQueryBuilder constructor.
     *
     * @param RandomParameterName $randomParameterNameService
     */
    public function __construct(RandomParameterName $randomParameterNameService)
    {
        $this->parameterNameGenerator = $randomParameterNameService;
    }

    /**
     * {@inheritdoc}
     */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.basic';
    }

    /**
     * {@inheritdoc}
     */
    public function applyQuery(QueryBuilder $queryBuilder, LeadSegmentFilter $filter)
    {
        $filterOperator = $filter->getOperator();
        $filterGlue     = $filter->getGlue();
        $filterAggr     = $filter->getAggregateFunction();

        //  Verify the column exists in database, this might be removed after tested
        $filter->getColumn();

        $filterParameters = $filter->getParameterValue();

        if (is_array($filterParameters)) {
            $parameters = [];
            foreach ($filterParameters as $filterParameter) {
                $parameters[] = $this->generateRandomParameterName();
            }
        } else {
            $parameters = $this->generateRandomParameterName();
        }

        $filterParametersHolder = $filter->getParameterHolder($parameters);

        $filterGlueFunc = $filterGlue.'Where';

        $tableAlias = $queryBuilder->getTableAlias($filter->getTable());

        // for aggregate function we need to create new alias and not reuse the old one
        if ($filterAggr) {
            $tableAlias = false;
        }

        if (!$tableAlias) {
            $tableAlias = $this->generateRandomParameterName();

            switch ($filterOperator) {
                case 'notLike':
                case 'notIn':
                case 'empty':
                case 'startsWith':
                case 'gt':
                case 'eq':
                case 'neq':
                case 'gte':
                case 'like':
                case 'lt':
                case 'lte':
                case 'in':
                case 'regexp':
                case 'notRegexp':
                    //@todo this logic needs to
                    if ($filterAggr) {
                        $queryBuilder->leftJoin(
                            $queryBuilder->getTableAlias('leads'),
                            $filter->getTable(),
                            $tableAlias,
                            sprintf('%s.id = %s.lead_id', $queryBuilder->getTableAlias('leads'), $tableAlias)
                        );
                    } else {
                        if ($filter->getTable() == 'companies') {
                            $relTable = $this->generateRandomParameterName();
                            $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'companies_leads', $relTable, $relTable.'.lead_id = l.id');
                            $queryBuilder->leftJoin($relTable, $filter->getTable(), $tableAlias, $tableAlias.'.id = '.$relTable.'.company_id');
                        } else {
                            $queryBuilder->leftJoin(
                                $queryBuilder->getTableAlias('leads'),
                                $filter->getTable(),
                                $tableAlias,
                                sprintf('%s.id = %s.lead_id', $queryBuilder->getTableAlias('leads'), $tableAlias)
                            );
                        }
                    }
                    break;
                default:
                    throw new \Exception('Dunno how to handle operator "'.$filterOperator.'"');
            }
        }

        switch ($filterOperator) {
            case 'empty':
                $expression = $queryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField());

                break;
            case 'notEmpty':
                $expression = $queryBuilder->expr()->isNotNull($tableAlias.'.'.$filter->getField());
                break;
            case 'startsWith':
            case 'endsWith':
            case 'gt':
            case 'eq':
            case 'neq':
            case 'gte':
            case 'like':
            case 'notLike':
            case 'lt':
            case 'lte':
            case 'notIn':
            case 'in':
            case 'regexp':
            case 'notRegexp':
                if ($filterAggr) {
                    $expression = $queryBuilder->expr()->$filterOperator(
                        sprintf('%s(%s)', $filterAggr, $tableAlias.'.'.$filter->getField()),
                        $filterParametersHolder
                    );
                } else {
                    $expression = $queryBuilder->expr()->$filterOperator(
                        $tableAlias.'.'.$filter->getField(),
                        $filterParametersHolder
                    );
                }
                break;
            default:
                throw new \Exception('Dunno how to handle operator "'.$filterOperator.'"');
        }

        if ($queryBuilder->isJoinTable($filter->getTable())) {
            if ($filterAggr) {
                $queryBuilder->andHaving($expression);
            } else {
                $queryBuilder->addJoinCondition($tableAlias, ' ('.$expression.')');
            }
        } else {
            $queryBuilder->$filterGlueFunc($expression);
        }

        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }

    /**
     * @param RandomParameterName $parameterNameGenerator
     *
     * @return BaseFilterQueryBuilder
     */
    public function setParameterNameGenerator($parameterNameGenerator)
    {
        $this->parameterNameGenerator = $parameterNameGenerator;

        return $this;
    }

    /**
     * @return string
     */
    protected function generateRandomParameterName()
    {
        return $this->parameterNameGenerator->generateRandomParameterName();
    }
}
