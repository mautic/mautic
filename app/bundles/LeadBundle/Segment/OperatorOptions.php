<?php

namespace Mautic\LeadBundle\Segment;

class OperatorOptions
{
    public const EQUAL_TO              = '=';

    public const NOT_EQUAL_TO          = '!=';

    public const GREATER_THAN          = 'gt';

    public const GREATER_THAN_OR_EQUAL = 'gte';

    public const LESS_THAN             = 'lt';

    public const LESS_THAN_OR_EQUAL    = 'lte';

    public const EMPTY                 = 'empty';

    public const NOT_EMPTY             = '!empty';

    public const LIKE                  = 'like';

    public const NOT_LIKE              = '!like';

    public const BETWEEN               = 'between';

    public const NOT_BETWEEN           = '!between';

    public const IN                    = 'in';

    public const NOT_IN                = '!in';

    public const REGEXP                = 'regexp';

    public const NOT_REGEXP            = '!regexp';

    public const DATE                  = 'date';

    public const STARTS_WITH           = 'startsWith';

    public const ENDS_WITH             = 'endsWith';

    public const CONTAINS              = 'contains';

    /**
     * @var array<string,array<string,string|bool>>
     */
    private static array $operatorOptions = [
        self::EQUAL_TO => [
            'label'       => 'mautic.lead.list.form.operator.equals',
            'expr'        => 'eq',
            'negate_expr' => 'neq',
        ],
        self::NOT_EQUAL_TO => [
            'label'       => 'mautic.lead.list.form.operator.notequals',
            'expr'        => 'neq',
            'negate_expr' => 'eq',
        ],
        self::GREATER_THAN => [
            'label'       => 'mautic.lead.list.form.operator.greaterthan',
            'expr'        => 'gt',
            'negate_expr' => 'lt',
        ],
        self::GREATER_THAN_OR_EQUAL => [
            'label'       => 'mautic.lead.list.form.operator.greaterthanequals',
            'expr'        => 'gte',
            'negate_expr' => 'lt',
        ],
        self::LESS_THAN => [
            'label'       => 'mautic.lead.list.form.operator.lessthan',
            'expr'        => 'lt',
            'negate_expr' => 'gt',
        ],
        self::LESS_THAN_OR_EQUAL => [
            'label'       => 'mautic.lead.list.form.operator.lessthanequals',
            'expr'        => 'lte',
            'negate_expr' => 'gt',
        ],
        self::EMPTY => [
            'label'       => 'mautic.lead.list.form.operator.isempty',
            'expr'        => 'empty', // special case
            'negate_expr' => 'notEmpty',
        ],
        self::NOT_EMPTY => [
            'label'       => 'mautic.lead.list.form.operator.isnotempty',
            'expr'        => 'notEmpty', // special case
            'negate_expr' => 'empty',
        ],
        self::LIKE => [
            'label'       => 'mautic.lead.list.form.operator.islike',
            'expr'        => 'like',
            'negate_expr' => 'notLike',
        ],
        self::NOT_LIKE => [
            'label'       => 'mautic.lead.list.form.operator.isnotlike',
            'expr'        => 'notLike',
            'negate_expr' => 'like',
        ],
        self::BETWEEN => [
            'label'       => 'mautic.lead.list.form.operator.between',
            'expr'        => 'between', // special case
            'negate_expr' => 'notBetween',
            'hide'        => true,
        ],
        self::NOT_BETWEEN => [
            'label'       => 'mautic.lead.list.form.operator.notbetween',
            'expr'        => 'notBetween', // special case
            'negate_expr' => 'between',
            'hide'        => true,
        ],
        self::IN => [
            'label'       => 'mautic.lead.list.form.operator.in',
            'expr'        => 'in',
            'negate_expr' => 'notIn',
        ],
        self::NOT_IN => [
            'label'       => 'mautic.lead.list.form.operator.notin',
            'expr'        => 'notIn',
            'negate_expr' => 'in',
        ],
        self::REGEXP => [
            'label'       => 'mautic.lead.list.form.operator.regexp',
            'expr'        => 'regexp', // special case
            'negate_expr' => 'notRegexp',
        ],
        self::NOT_REGEXP => [
            'label'       => 'mautic.lead.list.form.operator.notregexp',
            'expr'        => 'notRegexp', // special case
            'negate_expr' => 'regexp',
        ],
        self::DATE => [
            'label'       => 'mautic.lead.list.form.operator.date',
            'expr'        => 'date', // special case
            'negate_expr' => 'date',
            'hide'        => true,
        ],
        self::STARTS_WITH => [
            'label'       => 'mautic.core.operator.starts.with',
            'expr'        => 'startsWith',
            'negate_expr' => 'startsWith',
        ],
        self::ENDS_WITH => [
            'label'       => 'mautic.core.operator.ends.with',
            'expr'        => 'endsWith',
            'negate_expr' => 'endsWith',
        ],
        self::CONTAINS => [
            'label'       => 'mautic.core.operator.contains',
            'expr'        => 'contains',
            'negate_expr' => 'contains',
        ],
    ];

    /**
     * @return array<string,array<string,string>>
     */
    public static function getFilterExpressionFunctions()
    {
        return self::$operatorOptions;
    }

    /**
     * @return array<string,array<string,string>>
     */
    public function getFilterExpressionFunctionsNonStatic()
    {
        return self::$operatorOptions;
    }
}
