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
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Class DoNotContactFilterQueryBuilder.
 */
class DoNotContactFilterQueryBuilder extends BaseFilterQueryBuilder
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
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter)
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

        if ($filter->getOperator() === 'eq') {
            $queryType = $filter->getParameterValue() ? 'isNotNull' : 'isNull';
        } else {
            $queryType = $filter->getParameterValue() ? 'isNull' : 'isNotNull';
        }

        $queryBuilder->andWhere($queryBuilder->expr()->$queryType($tableAlias.'.id'));

        $queryBuilder->setParameter($exprParameter, ($parts[1] === 'bounced') ? DoNotContact::BOUNCED : DoNotContact::UNSUBSCRIBED);
        $queryBuilder->setParameter($channelParameter, $channel);

        return $queryBuilder;
    }
}
