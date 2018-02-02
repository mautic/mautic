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

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Segment\LeadSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Class DncFilterQueryBuilder.
 */
class DncFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /**
     * {@inheritdoc}
     */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.special.dnc';
    }

    /**
     * {@inheritdoc}
     */
    public function applyQuery(QueryBuilder $queryBuilder, LeadSegmentFilter $filter)
    {
        //@todo look at this, the getCrate method is for debuggin only
        $parts   = explode('_', $filter->getCrate('field'));
        $channel = 'email';

        if (count($parts) === 3) {
            $channel = $parts[2];
        }

        $tableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'left');

        if (!$tableAlias) {
            $tableAlias = $this->generateRandomParameterName();
            $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_donotcontact', $tableAlias, $tableAlias.'.lead_id = l.id');
        }

        $exprParameter    = $this->generateRandomParameterName();
        $channelParameter = $this->generateRandomParameterName();

        $expression = $queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq($tableAlias.'.reason', ":$exprParameter"),
            $queryBuilder->expr()
              ->eq($tableAlias.'.channel', ":$channelParameter")
        );

        $queryBuilder->addJoinCondition($tableAlias, $expression);

        /*
         * 1) condition "eq with parameter value YES" and "neq with parameter value NO" are equal
         * 2) condition "eq with parameter value NO" and "neq with parameter value YES" are equal
         *
         * If we want to include unsubscribed people (option 1) - we need IS NOT NULL condition (give me people which exists in table)
         * If we do not want to include unsubscribed people (option 2) - we need IS NULL condition
         *
         * @todo refactor this piece of code
         */
        if ($filter->getOperator() === 'eq') {
            if ($filter->getParameterValue() === true) {
                $queryType = 'isNotNull';
            } else {
                $queryType = 'isNull';
            }
        } else {
            if ($filter->getParameterValue() === true) {
                $queryType = 'isNull';
            } else {
                $queryType = 'isNotNull';
            }
        }

        $queryBuilder->andWhere($queryBuilder->expr()->$queryType($tableAlias.'.id'));

        $queryBuilder->setParameter($exprParameter, ($parts[1] === 'bounced') ? DoNotContact::BOUNCED : DoNotContact::UNSUBSCRIBED);
        $queryBuilder->setParameter($channelParameter, $channel);

        return $queryBuilder;
    }
}
