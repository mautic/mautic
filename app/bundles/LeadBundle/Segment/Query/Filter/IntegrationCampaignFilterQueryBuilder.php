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
 * Class IntegrationCampaignFilterQueryBuilder.
 */
class IntegrationCampaignFilterQueryBuilder extends BaseFilterQueryBuilder
{
    /**
     * {@inheritdoc}
     */
    public static function getServiceId()
    {
        return 'mautic.lead.query.builder.special.integration';
    }

    /**
     * {@inheritdoc}
     */
    public function applyQuery(QueryBuilder $queryBuilder, ContactSegmentFilter $filter)
    {
        $integrationCampaignParts = $filter->getIntegrationCampaignParts();

        $integrationNameParameter    = $this->generateRandomParameterName();
        $campaignIdParameter         = $this->generateRandomParameterName();

        $tableAlias = $this->generateRandomParameterName();

        $queryBuilder->leftJoin(
            'l',
            MAUTIC_TABLE_PREFIX.'integration_entity',
            $tableAlias,
            $tableAlias.'.integration_entity = "CampaignMember" AND '.
            $tableAlias.".internal_entity = 'lead' AND ".
            $tableAlias.'.internal_entity_id = l.id'
        );

        $expression = $queryBuilder->expr()->andX(
            $queryBuilder->expr()->eq($tableAlias.'.integration', ":$integrationNameParameter"),
            $queryBuilder->expr()->eq($tableAlias.'.integration_entity_id', ":$campaignIdParameter")
        );

        $queryBuilder->addJoinCondition($tableAlias, $expression);

        if ($filter->getOperator() === 'eq') {
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
