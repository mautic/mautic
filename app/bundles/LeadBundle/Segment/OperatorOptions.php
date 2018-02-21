<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment;

class OperatorOptions
{
    private static $operatorOptions = [
        '=' => [
            'label'       => 'mautic.lead.list.form.operator.equals',
            'expr'        => 'eq',
            'negate_expr' => 'neq',
        ],
        '!=' => [
            'label'       => 'mautic.lead.list.form.operator.notequals',
            'expr'        => 'neq',
            'negate_expr' => 'eq',
        ],
        'gt' => [
            'label'       => 'mautic.lead.list.form.operator.greaterthan',
            'expr'        => 'gt',
            'negate_expr' => 'lt',
        ],
        'gte' => [
            'label'       => 'mautic.lead.list.form.operator.greaterthanequals',
            'expr'        => 'gte',
            'negate_expr' => 'lt',
        ],
        'lt' => [
            'label'       => 'mautic.lead.list.form.operator.lessthan',
            'expr'        => 'lt',
            'negate_expr' => 'gt',
        ],
        'lte' => [
            'label'       => 'mautic.lead.list.form.operator.lessthanequals',
            'expr'        => 'lte',
            'negate_expr' => 'gt',
        ],
        'empty' => [
            'label'       => 'mautic.lead.list.form.operator.isempty',
            'expr'        => 'empty', //special case
            'negate_expr' => 'notEmpty',
        ],
        '!empty' => [
            'label'       => 'mautic.lead.list.form.operator.isnotempty',
            'expr'        => 'notEmpty', //special case
            'negate_expr' => 'empty',
        ],
        'like' => [
            'label'       => 'mautic.lead.list.form.operator.islike',
            'expr'        => 'like',
            'negate_expr' => 'notLike',
        ],
        '!like' => [
            'label'       => 'mautic.lead.list.form.operator.isnotlike',
            'expr'        => 'notLike',
            'negate_expr' => 'like',
        ],
        'between' => [
            'label'       => 'mautic.lead.list.form.operator.between',
            'expr'        => 'between', //special case
            'negate_expr' => 'notBetween',
            // @TODO implement in list UI
            'hide' => true,
        ],
        '!between' => [
            'label'       => 'mautic.lead.list.form.operator.notbetween',
            'expr'        => 'notBetween', //special case
            'negate_expr' => 'between',
            // @TODO implement in list UI
            'hide' => true,
        ],
        'in' => [
            'label'       => 'mautic.lead.list.form.operator.in',
            'expr'        => 'in',
            'negate_expr' => 'notIn',
        ],
        '!in' => [
            'label'       => 'mautic.lead.list.form.operator.notin',
            'expr'        => 'notIn',
            'negate_expr' => 'in',
        ],
        'regexp' => [
            'label'       => 'mautic.lead.list.form.operator.regexp',
            'expr'        => 'regexp', //special case
            'negate_expr' => 'notRegexp',
        ],
        '!regexp' => [
            'label'       => 'mautic.lead.list.form.operator.notregexp',
            'expr'        => 'notRegexp', //special case
            'negate_expr' => 'regexp',
        ],
        'date' => [
            'label'       => 'mautic.lead.list.form.operator.date',
            'expr'        => 'date', //special case
            'negate_expr' => 'date',
            'hide'        => true,
        ],
        'startsWith' => [
            'label'       => 'mautic.core.operator.starts.with',
            'expr'        => 'startsWith',
            'negate_expr' => 'startsWith',
        ],
        'endsWith' => [
            'label'       => 'mautic.core.operator.ends.with',
            'expr'        => 'endsWith',
            'negate_expr' => 'endsWith',
        ],
        'contains' => [
            'label'       => 'mautic.core.operator.contains',
            'expr'        => 'contains',
            'negate_expr' => 'contains',
        ],
    ];

    public static function getFilterExpressionFunctions()
    {
        return self::$operatorOptions;
    }

    public function getFilterExpressionFunctionsNonStatic()
    {
        return self::$operatorOptions;
    }
}
