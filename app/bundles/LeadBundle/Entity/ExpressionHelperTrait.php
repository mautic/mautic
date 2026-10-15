<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

trait ExpressionHelperTrait
{
    /**
     * @param QueryBuilder|\Doctrine\ORM\QueryBuilder $q
     * @param bool|null                               $includeIsNull null to auto determine based on operator
     *
     * @return mixed
     */
    public function generateFilterExpression($q, $column, $operator, $parameter, $includeIsNull, ?CompositeExpression $appendTo = null)
    {
        // in/notIn for dbal will use a raw array
        if (!is_array($parameter) && !str_starts_with($parameter, ':')) {
            $parameter = ":{$parameter}";
        }

        if (null === $includeIsNull) {
            // Auto determine based on negate operators
            $includeIsNull = in_array($operator, ['neq', 'notLike', 'notIn']);
        }

        $expression = in_array($operator, ['like', 'notLike'], true)
            ? DatabasePlatform::getCaseInsensitiveLike(
                $this->getEntityManager()->getConnection()->getDatabasePlatform(),
                $column,
                $parameter,
                'notLike' === $operator ? DatabasePlatform::FLAG_NEGATIVE : 0
            )
            : $q->expr()->{$operator}($column, $parameter);

        if ($includeIsNull) {
            $expr = $q->expr()->or(
                $expression,
                $q->expr()->isNull($column)
            );
        } else {
            $expr = $expression;
        }

        if ($appendTo) {
            return $appendTo->with($expr);
        }

        return $expr;
    }
}
