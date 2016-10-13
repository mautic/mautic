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

trait OperatorListTrait
{
    /**
     * @param null $operator
     *
     * @return array
     */
    public function getFilterExpressionFunctions($operator = null)
    {
        $operatorOptions = [
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
                // @todo implement in list UI
                'hide' => true,
            ],
            '!between' => [
                'label'       => 'mautic.lead.list.form.operator.notbetween',
                'expr'        => 'notBetween', //special case
                'negate_expr' => 'between',
                // @todo implement in list UI
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
        ];

        return ($operator === null) ? $operatorOptions : $operatorOptions[$operator];
    }
}
