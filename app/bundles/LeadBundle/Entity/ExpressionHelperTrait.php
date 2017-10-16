<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;

trait ExpressionHelperTrait
{
    /**
     * @param QueryBuilder|\Doctrine\ORM\QueryBuilder $q
     * @param $column
     * @param $operator
     * @param $parameter
     * @param $includeIsNull    true/false or null to auto determine based on operator
     *
     * @return mixed
     */
    public function generateFilterExpression($q, $column, $operator, $parameter, $includeIsNull, CompositeExpression $appendTo = null)
    {
        // in/notIn for dbal will use a raw array
        if (!is_array($parameter) && strpos($parameter, ':') !== 0) {
            $parameter = ":$parameter";
        }

        if (null === $includeIsNull) {
            // Auto determine based on negate operators
            $includeIsNull = (in_array($operator, ['neq', 'notLike', 'notIn']));
        }

        if ($includeIsNull) {
            $expr = $q->expr()->orX(
                $q->expr()->$operator($column, $parameter),
                $q->expr()->isNull($column)
            );
        } else {
            $expr = $q->expr()->$operator($column, $parameter);
        }

        if ($appendTo) {
            $appendTo->add($expr);

            return $appendTo;
        }

        return $expr;
    }
}
