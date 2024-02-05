<?php

namespace Mautic\LeadBundle\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryException;

class IntegrationCampaignFilterQueryBuilder extends BaseFilterQueryBuilder
{
    public static function getServiceId(): string
    {
        return 'mautic.lead.query.builder.special.integration';
    }

    /**
     * @throws QueryException
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter): QueryBuilder
    {
        $leadsTableAlias          = $queryBuilder->getTableAlias(MAUTIC_TABLE_PREFIX.'leads');
        $integrationCampaignParts = $filter->getIntegrationCampaignParts();

        $integrationNameParameter    = $this->generateRandomParameterName();
        $campaignIdParameter         = $this->generateRandomParameterName();

        $tableAlias = $this->generateRandomParameterName();

        $queryBuilder->leftJoin(
            $leadsTableAlias,
            MAUTIC_TABLE_PREFIX.'integration_entity',
            $tableAlias,
            $tableAlias.'.integration_entity = "CampaignMember" AND '.
            $tableAlias.".internal_entity = 'lead' AND ".
            $tableAlias.'.internal_entity_id = '.$leadsTableAlias.'.id'
        );

        $expression = $queryBuilder->expr()->and(
            $queryBuilder->expr()->eq($tableAlias.'.integration', ":$integrationNameParameter"),
            $queryBuilder->expr()->eq($tableAlias.'.integration_entity_id', ":$campaignIdParameter")
        );

        $queryBuilder->addJoinCondition($tableAlias, $expression);

        if ('eq' === $filter->getOperator()) {
            $queryType = $filter->getParameterValue() ? 'isNotNull' : 'isNull';
        } else {
            $queryType = $filter->getParameterValue() ? 'isNull' : 'isNotNull';
        }

        $queryBuilder->addLogic($queryBuilder->expr()->$queryType($tableAlias.'.id'), $filter->getGlue());

        $queryBuilder->setParameter($integrationNameParameter, $integrationCampaignParts->getIntegrationName());
        $queryBuilder->setParameter($campaignIdParameter, $integrationCampaignParts->getCampaignId());

        return $queryBuilder;
    }
}
