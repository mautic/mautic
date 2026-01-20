<?php

namespace Mautic\LeadBundle\Segment\Query\Expression;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder as BaseExpressionBuilder;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\LeadBundle\Segment\Exception\SegmentQueryException;

class ExpressionBuilder extends BaseExpressionBuilder
{
    public const REGEXP  = 'REGEXP';

    public const BETWEEN = 'BETWEEN';

    private string $platform;

    public function __construct(Connection $connection)
    {
        $this->platform = DatabasePlatform::getDatabasePlatform($connection->getDatabasePlatform());
        parent::__construct($connection);
    }

    /**
     * Creates a between comparison expression.
     *
     * @throws SegmentQueryException
     */
    public function between($x, $arr): string
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
     * @throws SegmentQueryException
     */
    public function notBetween($x, $arr): string
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
        if ('postgresql' == $this->platform) {
            return $this->comparison($x, '~*', $y);
        } else {
            return $this->comparison($x, self::REGEXP, $y);
        }
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
     */
    public function notRegexp($x, $y): string
    {
        if ('postgresql' == $this->platform) {
            return $this->comparison($x, '!~*', $y);
        } else {
            return 'NOT '.$this->comparison($x, self::REGEXP, $y);
        }
    }

    /**
     * Puts argument into EXISTS mysql function.
     */
    public function exists($input): string
    {
        return $this->func('EXISTS', $input);
    }

    /**
     * Puts argument into NOT EXISTS mysql function.
     */
    public function notExists($input): string
    {
        return $this->func('NOT EXISTS', $input);
    }

    /**
     * Creates a functional expression.
     *
     * @param string       $func any function to be applied on $x
     * @param mixed        $x    the left expression
     * @param string|array $y    the placeholder or the array of values to be used by IN() comparison
     */
    public function func($func, $x, $y = null): string
    {
        $functionArguments = func_get_args();
        $additionArguments = array_splice($functionArguments, 2);

        foreach ($additionArguments as $k=> $v) {
            $additionArguments[$k] = is_numeric($v) && intval($v) === $v ? $v : $this->literal($v);
        }

        return $func.'('.$x.(count($additionArguments) ? ', ' : '').join(',', $additionArguments).')';
    }
}
