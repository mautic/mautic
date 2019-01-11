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

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression;
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
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter)
    {
        $filterOperator = $filter->getOperator();
        $filterGlue     = $filter->getGlue();

        // Check if the column exists in the table
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

        $tableAlias      = $this->generateRandomParameterName();
        $subQueryBuilder = $queryBuilder->getConnection()->createQueryBuilder();
        $subQueryBuilder
            ->select('NULL')->from($filter->getTable(), $tableAlias)
            ->andWhere($tableAlias.'.id = l.id');

        switch ($filterOperator) {
            case 'empty':
                $expression = new CompositeExpression(CompositeExpression::TYPE_OR,
                    [
                        $queryBuilder->expr()->isNull('l.'.$filter->getField()),
                        $queryBuilder->expr()->eq('l.'.$filter->getField(), $queryBuilder->expr()->literal('')),
                    ]
                );
                break;
            case 'notEmpty':
                $expression = new CompositeExpression(CompositeExpression::TYPE_AND,
                    [
                        $queryBuilder->expr()->isNotNull('l.'.$filter->getField()),
                        $queryBuilder->expr()->neq('l.'.$filter->getField(), $queryBuilder->expr()->literal('')),
                    ]
                );

                break;
            case 'neq':
                $expression = $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->isNull('l.'.$filter->getField()),
                    $queryBuilder->expr()->$filterOperator(
                        'l.'.$filter->getField(),
                        $filterParametersHolder
                    )
                );
                break;
            case 'startsWith':
            case 'endsWith':
            case 'gt':
            case 'eq':
            case 'gte':
            case 'like':
            case 'lt':
            case 'lte':
            case 'in':
            case 'between':   //Used only for date with week combination (EQUAL [this week, next week, last week])
            case 'regexp':
            case 'notRegexp': //Different behaviour from 'notLike' because of BC (do not use condition for NULL). Could be changed in Mautic 3.
                $expression = $queryBuilder->expr()->$filterOperator(
                    'l.'.$filter->getField(),
                    $filterParametersHolder
                );
                break;
            case 'notLike':
            case 'notBetween': //Used only for date with week combination (NOT EQUAL [this week, next week, last week])
            case 'notIn':
                $expression = $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->$filterOperator('l.'.$filter->getField(), $filterParametersHolder),
                    $queryBuilder->expr()->isNull('l.'.$filter->getField())
                );
                break;
            case 'notGt':
            case 'notLt':
                $expr       = strtolower(str_replace('not', '', $filterOperator));
                $expression = $subQueryBuilder->expr()->orX(
                    $subQueryBuilder->expr()->isNull($tableAlias.'.'.$filter->getField()),
                    $subQueryBuilder->expr()->$expr($tableAlias.'.'.$filter->getField(), $filterParametersHolder)
                );

                $subQueryBuilder->andWhere($expression);

                $queryBuilder->addLogic($queryBuilder->expr()->notExists($subQueryBuilder->getSQL()), $filter->getGlue());
                break;
            case 'multiselect':
            case '!multiselect':
                $operator    = $filterOperator === 'multiselect' ? 'regexp' : 'notRegexp';
                $expressions = [];
                foreach ($filterParametersHolder as $parameter) {
                    $expressions[] = $queryBuilder->expr()->$operator('l.'.$filter->getField(), $parameter);
                }

                $expression = $queryBuilder->expr()->andX($expressions);
                break;
            default:
                throw new \Exception('Dunno how to handle operator "'.$filterOperator.'"');
        }

        if (!in_array($filterOperator, ['notGt', 'notLt'])) {
            $queryBuilder->addLogic($expression, $filterGlue);
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
