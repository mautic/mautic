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
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Class ChannelClickQueryBuilder.
 */
class ChannelClickQueryBuilder extends BaseFilterQueryBuilder
{
    /**
     * @return string
     */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.channel_click.value';
    }

    /**
     * @return QueryBuilder
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter)
    {
        $filterOperator = $filter->getOperator();
        $filterChannel  = $this->getChannel($filter->getField());

        $filterParameter = $filter->getParameterValue();
        $parameter       = $this->generateRandomParameterName();

        $tableAlias = $this->generateRandomParameterName();

        $subQb = $queryBuilder->getConnection()->createQueryBuilder();
        $expr  = $subQb->expr()->andX(
            $subQb->expr()->isNotNull($tableAlias.'.redirect_id'),
            $subQb->expr()->isNotNull($tableAlias.'.lead_id'),
            $subQb->expr()->eq($tableAlias.'.source', $subQb->expr()->literal($filterChannel))
        );

        $inExpr = OperatorOptions::NOT_EQUAL_TO === $filterOperator ? 'notIn' : 'in';
        if ($this->isDateBased($filter->getField())) {
            $expr->add(
                $subQb->expr()->$filterOperator($tableAlias.'.date_hit', $filter->getParameterHolder($parameter))
            );
        } elseif (empty($filterParameter) && in_array($filterOperator, [OperatorOptions::NOT_EQUAL_TO, OperatorOptions::NOT_EMPTY])) {
            // value != 0
            $inExpr = 'in';
        } elseif (empty($filterParameter) && in_array($filterOperator, [OperatorOptions::EQUAL_TO, OperatorOptions::EMPTY])) {
            // value = 0
            $inExpr = 'notIn';
        }

        $subQb->select($tableAlias.'.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', $tableAlias)
            ->where($expr);

        $queryBuilder->addLogic($queryBuilder->expr()->$inExpr('l.id', $subQb->getSQL()), $filter->getGlue());

        $queryBuilder->setParametersPairs($parameter, $filterParameter);

        return $queryBuilder;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    private function getChannel($name)
    {
        if ('email_id' === $name) {
            // BC for existing filter
            return 'email';
        }

        return str_replace(['_clicked_link', '_date'], '', $name);
    }

    /**
     * @param $name
     *
     * @return bool
     */
    private function isDateBased($name)
    {
        return false !== strpos($name, '_date');
    }
}
