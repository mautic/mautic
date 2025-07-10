<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\LeadBatchLimiterTrait;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

class ChannelClickQueryBuilder extends BaseFilterQueryBuilder
{
    use LeadBatchLimiterTrait;

    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.channel_click.value';
    }

    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $leadsTableAlias  = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $filterOperator   = $filter->getOperator();
        $filterChannel    = $this->getChannel($filter->getField());
        $batchLimiters    = $filter->getBatchLimiters();
        $filterParameters = $filter->getParameterValue();

        if (is_array($filterParameters)) {
            $parameters = [];
            foreach ($filterParameters as $filterParameter) {
                $parameters[] = $this->generateRandomParameterName();
            }
        } else {
            $parameters = $this->generateRandomParameterName();
        }

        $tableAlias = $this->generateRandomParameterName();

        $subQb = $queryBuilder->createQueryBuilder();
        $expr  = $subQb->expr()->and(
            $subQb->expr()->isNotNull($tableAlias.'.redirect_id'),
            $subQb->expr()->isNotNull($tableAlias.'.lead_id'),
            $subQb->expr()->eq($tableAlias.'.source', $subQb->expr()->literal($filterChannel))
        );

        if ($this->isDateBased($filter->getField())) {
            $expr = $expr->with(
                $subQb->expr()->$filterOperator($tableAlias.'.date_hit', $filter->getParameterHolder($parameters))
            );
        }

        $subQb->select($tableAlias.'.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', $tableAlias)
            ->where($expr);

        $this->addLeadAndMinMaxLimiters($subQb, $batchLimiters, 'page_hits');

        if ('empty' === $filterOperator && !$this->isDateBased($filter->getField())) {
            $queryBuilder->addLogic($queryBuilder->expr()->notIn($leadsTableAlias.'.id', $subQb->getSQL()), $filter->getGlue());
        } else {
            $queryBuilder->addLogic($queryBuilder->expr()->in($leadsTableAlias.'.id', $subQb->getSQL()), $filter->getGlue());
        }

        $queryBuilder->setParametersPairs($parameters, $filterParameters);

        return $queryBuilder;
    }

    private function getChannel(string $name): string
    {
        if ('email_id' === $name) {
            // BC for existing filter
            return 'email';
        }

        return str_replace(['_clicked_link', '_date'], '', $name);
    }

    private function isDateBased(string $name): bool
    {
        return str_contains($name, '_date');
    }
}
