<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 1/11/18
 * Time: 11:24 AM.
 */

namespace Mautic\LeadBundle\Segment\FilterQueryBuilder;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Segment\LeadSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Services\LeadSegmentFilterQueryBuilderTrait;

class LeadListFilterQueryBuilder implements FilterQueryBuilderInterface
{
    use LeadSegmentFilterQueryBuilderTrait;

    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.special.leadlist';
    }

    public function applyQuery(QueryBuilder $queryBuilder, LeadSegmentFilter $filter)
    {
        dump('This is definitely an @todo!!!!');

        return $queryBuilder;
        dump('lead list');
        die();
        $parts   = explode('_', $filter->getCrate('field'));
        $channel = 'email';

        if (count($parts) === 3) {
            $channel = $parts[2];
        }

        $tableAlias = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'left');

        if (!$tableAlias) {
            $tableAlias = $this->generateRandomParameterName();
            $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'lead_donotcontact', $tableAlias, MAUTIC_TABLE_PREFIX.'lead_donotcontact.lead_id = l.id');
        }

        $exprParameter    = $this->generateRandomParameterName();
        $channelParameter = $this->generateRandomParameterName();

        $expression = $queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq($tableAlias.'.reason', ":$exprParameter"),
            $queryBuilder->expr()
              ->eq($tableAlias.'.channel', ":$channelParameter")
        );

        $queryBuilder->addJoinCondition($tableAlias, $expression);

        $queryType = $filter->getOperator() === 'eq' ? 'isNull' : 'isNotNull';

        $queryBuilder->andWhere($queryBuilder->expr()->$queryType($tableAlias.'.id'));

        $queryBuilder->setParameter($exprParameter, ($parts[1] === 'bounced') ? DoNotContact::BOUNCED : DoNotContact::UNSUBSCRIBED);
        $queryBuilder->setParameter($channelParameter, $channel);

        return $queryBuilder;
    }
}
