<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Stats\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

trait FilterTrait
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param int|null $companyId
     * @param string   $fromAlias
     */
    protected function addCompanyFilter(QueryBuilder $q, $companyId = null, $fromAlias = 't')
    {
        if (null !== $companyId && intval($companyId)) {
            $sb = $this->connection->createQueryBuilder();

            $sb->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl')
                ->where(
                    $sb->expr()->andX(
                        $sb->expr()->eq('cl.company_id', ':companyId'),
                        $sb->expr()->eq('cl.lead_id', $fromAlias.'.lead_id')
                    )
                );

            $q->andWhere(
                sprintf('EXISTS (%s)', $sb->getSql())
            )->setParameter('companyId', $companyId);
        }
    }

    /**
     * @param int|null $campaignId
     * @param string   $fromAlias
     */
    protected function addCampaignFilter(QueryBuilder $q, $campaignId = null, $fromAlias = 't')
    {
        if (null !== $campaignId && intval($campaignId)) {
            $q->innerJoin($fromAlias, '(SELECT DISTINCT event_id, lead_id FROM '.MAUTIC_TABLE_PREFIX.'campaign_lead_event_log WHERE campaign_id = :campaignId)', 'clel', $fromAlias.'.source_id = clel.event_id AND '.$fromAlias.'.source = "campaign.event" AND '.$fromAlias.'.lead_id = clel.lead_id')
                ->setParameter('campaignId', $campaignId);
        }
    }

    /**
     * @param int|null $campaignId
     * @param string   $fromAlias
     */
    protected function addCampaignFilterForEmailSource(QueryBuilder $q, $campaignId = null, $fromAlias = 't')
    {
        if (null !== $campaignId && intval($campaignId)) {
            $q->innerJoin($fromAlias, '(SELECT DISTINCT channel_id, lead_id FROM '.MAUTIC_TABLE_PREFIX.'campaign_lead_event_log WHERE campaign_id = :campaignId AND channel = "email")', 'clel', $fromAlias.'.source_id = clel.channel_id AND '.$fromAlias.'.source = "email" AND '.$fromAlias.'.lead_id = clel.lead_id')
                ->setParameter('campaignId', $campaignId);
        }
    }

    /**
     * @param int|null $segmentId
     * @param string   $fromAlias
     */
    protected function addSegmentFilter(QueryBuilder $q, $segmentId = null, $fromAlias = 't')
    {
        if (null !== $segmentId && intval($segmentId)) {
            $sb = $this->connection->createQueryBuilder();

            $sb->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'lll')
                ->where(
                    $sb->expr()->andX(
                        $sb->expr()->eq('lll.leadlist_id', ':segmentId'),
                        $sb->expr()->eq('lll.lead_id', $fromAlias.'.lead_id'),
                        $sb->expr()->eq('lll.manually_removed', 0)
                    )
                );

            $q->andWhere(
                sprintf('EXISTS (%s)', $sb->getSql())
            )->setParameter('segmentId', $segmentId);
        }
    }
}
