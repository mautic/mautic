<?php

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\LeadBatchLimiterTrait;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryException;

class DoNotContactFilterQueryBuilder extends BaseFilterQueryBuilder
{
    use LeadBatchLimiterTrait;

    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.special.dnc';
    }

    /**
     * @throws QueryException
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $leadsTableAlias   = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $doNotContactParts = $filter->getDoNotContactParts();
        $batchLimiters     = $filter->getBatchLimiters();
        $expr              = $queryBuilder->expr();
        $queryAlias        = $this->generateRandomParameterName();

        // Handle the All DNC filter type
        if ($doNotContactParts->isAllDnc()) {
            $filterQueryBuilder = $queryBuilder->createQueryBuilder()
                ->select('DISTINCT '.$queryAlias.'.lead_id')
                ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', $queryAlias);

            $this->addLeadAndMinMaxLimiters($filterQueryBuilder, $batchLimiters, 'lead_donotcontact');

            if ('eq' === $filter->getOperator() xor !$filter->getParameterValue()) {
                $expression = $expr->in($leadsTableAlias.'.id', $filterQueryBuilder->getSQL());
            } else {
                $expression = $expr->notIn($leadsTableAlias.'.id', $filterQueryBuilder->getSQL());
            }

            $queryBuilder->addLogic($expression, $filter->getGlue());

            return $queryBuilder;
        }

        $reasonParameter   = "{$queryAlias}reason";
        $channelParameter  = "{$queryAlias}channel";

        $queryBuilder->setParameter($reasonParameter, $doNotContactParts->getParameterType());
        $queryBuilder->setParameter($channelParameter, $doNotContactParts->getChannel());

        $filterQueryBuilder = $queryBuilder->createQueryBuilder()
            ->select($queryAlias.'.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', $queryAlias)
            ->andWhere($expr->eq($queryAlias.'.reason', ':'.$reasonParameter))
            ->andWhere($expr->eq($queryAlias.'.channel', ':'.$channelParameter));

        // Handle comment-based filters (hard bounce, soft bounce, spam bounce)
        if ($commentFilter = $doNotContactParts->getCommentFilter()) {
            switch ($commentFilter) {
                case 'hard':
                    $orExpr = $expr->or(
                        $expr->like($queryAlias.'.comments', $expr->literal('%unrecognized address%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('5%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%5._._%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%maildir delivery failed%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%invalid%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%Bounced Address%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%Spam reporting address%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%does not exist%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%unknown%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%Incorrectly formatted email address%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%BOGON%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%User unsubscribed%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%Message delivery failed%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%not found%'))
                    );
                    $filterQueryBuilder->andWhere($orExpr);
                    // Ensure this only applies to bounced emails
                    $filterQueryBuilder->andWhere($expr->eq($queryAlias.'.reason', DoNotContact::BOUNCED));
                    break;

                case 'soft':
                    $orExpr = $expr->or(
                        $expr->like($queryAlias.'.comments', $expr->literal('4%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%4._._%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%timeout%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%connection refused%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%Connection reset by peer%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%Unable to parse reason from bounce report%'))
                    );
                    $filterQueryBuilder->andWhere($orExpr);
                    // Ensure this only applies to bounced emails
                    $filterQueryBuilder->andWhere($expr->eq($queryAlias.'.reason', DoNotContact::BOUNCED));
                    break;

                case 'spam':
                    $orExpr = $expr->or(
                        $expr->like($queryAlias.'.comments', $expr->literal('%spam%')),
                        $expr->like($queryAlias.'.comments', $expr->literal('%rejected%'))
                    );
                    $filterQueryBuilder->andWhere($orExpr);
                    // Ensure this only applies to bounced emails
                    $filterQueryBuilder->andWhere($expr->eq($queryAlias.'.reason', DoNotContact::BOUNCED));
                    break;
            }
        }

        $this->addLeadAndMinMaxLimiters($filterQueryBuilder, $batchLimiters, 'lead_donotcontact');

        if ('eq' === $filter->getOperator() xor !$filter->getParameterValue()) {
            $expression = $expr->in($leadsTableAlias.'.id', $filterQueryBuilder->getSQL());
        } else {
            $expression = $expr->notIn($leadsTableAlias.'.id', $filterQueryBuilder->getSQL());
        }

        $queryBuilder->addLogic($expression, $filter->getGlue());

        return $queryBuilder;
    }
}
