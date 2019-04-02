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
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Class SessionsFilterQueryBuilder.
 */
class SessionsFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /** {@inheritdoc} */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.special.sessions';
    }

    /** {@inheritdoc} */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter)
    {
        $pageHitsAlias        = $this->generateRandomParameterName();
        $exclusionAlias       = $this->generateRandomParameterName();
        $expressionValueAlias = $this->generateRandomParameterName();

        $expressionOperator = $filter->getOperator();
        $expression         = $queryBuilder->expr()->$expressionOperator('count(id)',
            $filter->getParameterHolder($expressionValueAlias));

        $queryBuilder->setParameter($expressionValueAlias, (int) $filter->getParameterValue());

        $exclusionQueryBuilder = $queryBuilder->getConnection()->createQueryBuilder();
        $exclusionQueryBuilder
            ->select($exclusionAlias.'.id')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', $exclusionAlias)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('l.id', $exclusionAlias.'.lead_id'),
                    $queryBuilder->expr()->gt(
                        $exclusionAlias.'.date_hit',
                        $pageHitsAlias.'.date_hit - INTERVAL 30 MINUTE'
                    ),
                    $queryBuilder->expr()->lt($exclusionAlias.'.date_hit', $pageHitsAlias.'.date_hit')
                )
            );

        $sessionQueryBuilder = $queryBuilder->getConnection()->createQueryBuilder();
        $sessionQueryBuilder
            ->select('count(id)')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', $pageHitsAlias)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('l.id', $pageHitsAlias.'.lead_id'),
                    $queryBuilder->expr()->isNull($pageHitsAlias.'.email_id'),
                    $queryBuilder->expr()->isNull($pageHitsAlias.'.redirect_id'),
                    $queryBuilder->expr()->notExists(
                        $exclusionQueryBuilder->getSQL()
                    )
                )
            )
            ->having($expression);

        $glue = $filter->getGlue().'Where';
        $queryBuilder->$glue($queryBuilder->expr()->exists($sessionQueryBuilder->getSQL()));

        return $queryBuilder;
    }
}
