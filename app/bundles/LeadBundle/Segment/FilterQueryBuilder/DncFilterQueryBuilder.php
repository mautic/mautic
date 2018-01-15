<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 1/11/18
 * Time: 11:24 AM.
 */

namespace Mautic\LeadBundle\Segment\FilterQueryBuilder;

use Mautic\LeadBundle\Segment\LeadSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Services\LeadSegmentFilterQueryBuilderTrait;

class DncFilterQueryBuilder implements FilterQueryBuilderInterface
{
    use LeadSegmentFilterQueryBuilderTrait;

    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.special.dnc';
    }

    public function aaa()
    {
        switch (false) {
            case
            'dnc_bounced':
            case 'dnc_unsubscribed':
            case 'dnc_bounced_sms':
            case 'dnc_unsubscribed_sms':
                // Special handling of do not contact

                $func = (($func === 'eq' && $leadSegmentFilter->getFilter()) || ($func === 'neq' && !$leadSegmentFilter->getFilter())) ? 'EXISTS' : 'NOT EXISTS';

                $parts   = explode('_', $leadSegmentFilter->getField());
                $channel = 'email';

                if (count($parts) === 3) {
                    $channel = $parts[2];
                }

                $channelParameter = $this->generateRandomParameterName();
                $subqb            = $this->entityManager->getConnection()->createQueryBuilder()
                                                        ->select('null')
                                                        ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', $alias)
                                                        ->where(
                                                            $q->expr()->andX(
                                                                $q->expr()->eq($alias.'.reason', $exprParameter),
                                                                $q->expr()->eq($alias.'.lead_id', 'l.id'),
                                                                $q->expr()
                                                                  ->eq($alias.'.channel', ":$channelParameter")
                                                            )
                                                        );

                $groupExpr->add(
                    sprintf('%s (%s)', $func, $subqb->getSQL())
                );

                // Filter will always be true and differentiated via EXISTS/NOT EXISTS
                $leadSegmentFilter->setFilter(true);

                $ignoreAutoFilter = true;

                $parameters[$parameter]        = ($parts[1] === 'bounced') ? DoNotContact::BOUNCED : DoNotContact::UNSUBSCRIBED;
                $parameters[$channelParameter] = $channel;

                break;
        }
    }

    public function applyQuery(QueryBuilder $queryBuilder, LeadSegmentFilter $filter)
    {
        dump('dnc apply query:');
        var_dump($filter);
        die();
        $parts   = explode('_', $filter->getField());
        $channel = 'email';

        if (count($parts) === 3) {
            $channel = $parts[2];
        }

        $channelParameter = $this->generateRandomParameterName();
        $subqb            = $this->entityManager->getConnection()->createQueryBuilder()
                                                ->select('null')
                                                ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', $alias)
                                                ->where(
                                                    $q->expr()->andX(
                                                        $q->expr()->eq($alias.'.reason', $exprParameter),
                                                        $q->expr()->eq($alias.'.lead_id', 'l.id'),
                                                        $q->expr()
                                                          ->eq($alias.'.channel', ":$channelParameter")
                                                    )
                                                );

        $groupExpr->add(
            sprintf('%s (%s)', $func, $subqb->getSQL())
        );

        // Filter will always be true and differentiated via EXISTS/NOT EXISTS
        $leadSegmentFilter->setFilter(true);

        $ignoreAutoFilter = true;

        $parameters[$parameter]        = ($parts[1] === 'bounced') ? DoNotContact::BOUNCED : DoNotContact::UNSUBSCRIBED;
        $parameters[$channelParameter] = $channel;

        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }
}
