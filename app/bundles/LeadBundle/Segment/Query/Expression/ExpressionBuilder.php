<?php

namespace Mautic\LeadBundle\Segment\Query\Expression;

use Mautic\LeadBundle\Segment\Exception\SegmentQueryException;

class ExpressionBuilder extends \Doctrine\DBAL\Query\Expression\ExpressionBuilder
{
    const REGEXP  = 'REGEXP';
    const BETWEEN = 'BETWEEN';

    public function andX($x = null): CompositeExpression
    {
        if (is_array($x)) {
            return new CompositeExpression(CompositeExpression::TYPE_AND, $x);
        }

        return new CompositeExpression(CompositeExpression::TYPE_AND, func_get_args());
    }

    public function orX($x = null): CompositeExpression
    {
        if (is_array($x)) {
            return new CompositeExpression(CompositeExpression::TYPE_OR, $x);
        }

        return new CompositeExpression(CompositeExpression::TYPE_OR, func_get_args());
    }

    /**
     * Creates a between comparison expression.
     *
     * @param $x
     * @param $arr
     *
     * @throws SegmentQueryException
     *
     * @return string
     */
    public function between($x, $arr)
    {
        if (!is_array($arr) || 2 != count($arr)) {
            throw new SegmentQueryException('Between expression expects second argument to be an array with exactly two elements');
        }

        return $x.' '.self::BETWEEN.' '.$this->comparison($arr[0], 'AND', $arr[1]);
    }

    /**
     * Creates a not between comparison expression.
     *
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> = <right expr>. Example:
     *
     *     [php]
     *     // u.id = ?
     *     $expr->eq('u.id', '?');
     *
     * @param $x
     * @param $arr
     *
     * @throws SegmentQueryException
     *
     * @return string
     */
    public function notBetween($x, $arr)
    {
        return 'NOT '.$this->between($x, $arr);
    }

    /**
     * Creates an equality comparison expression with the given arguments.
     *
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> = <right expr>. Example:
     *
     *     [php]
     *     // u.id = ?
     *     $expr->eq('u.id', '?');
     *
     * @param mixed $x the left expression
     * @param mixed $y the right expression
     *
     * @return string
     */
    public function regexp($x, $y)
    {
        return $this->comparison($x, self::REGEXP, $y);
    }

    /**
     * Creates an equality comparison expression with the given arguments.
     *
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> = <right expr>. Example:
     *
     *     [php]
     *     // u.id = ?
     *     $expr->eq('u.id', '?');
     *
     * @param mixed $x the left expression
     * @param mixed $y the right expression
     *
     * @return string
     */
    public function notRegexp($x, $y)
    {
        return 'NOT '.$this->comparison($x, self::REGEXP, $y);
    }

    /**
     * Puts argument into EXISTS mysql function.
     *
     * @param $input
     *
     * @return string
     */
    public function exists($input)
    {
        return $this->func('EXISTS', $input);
    }

    /**
     * Puts argument into NOT EXISTS mysql function.
     *
     * @param $input
     *
     * @return string
     */
    public function notExists($input)
    {
        return $this->func('NOT EXISTS', $input);
    }

    /**
     * Creates a functional expression.
     *
     * @param string       $func any function to be applied on $x
     * @param mixed        $x    the left expression
     * @param string|array $y    the placeholder or the array of values to be used by IN() comparison
     *
     * @return string
     */
    public function func($func, $x, $y = null)
    {
        $functionArguments = func_get_args();
        $additionArguments = array_splice($functionArguments, 2);

        foreach ($additionArguments as $k=> $v) {
            $additionArguments[$k] = is_numeric($v) && intval($v) === $v ? $v : $this->literal($v);
        }

        return  $func.'('.$x.(count($additionArguments) ? ', ' : '').join(',', $additionArguments).')';
    }
}
