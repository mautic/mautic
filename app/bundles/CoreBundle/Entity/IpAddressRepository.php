<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\DBAL\Exception;

/**
 * @extends CommonRepository<IpAddress>
 */
class IpAddressRepository extends CommonRepository
{
    /**
     * Count how many unique IP addresses is there.
     */
    public function countIpAddresses(): int
    {
        $q = $this->createQueryBuilder('i');
        $q->select('COUNT(DISTINCT i.id) as unique');
        $results = $q->getQuery()->getSingleResult();

        if (!isset($results['unique'])) {
            return 0;
        }

        return (int) $results['unique'];
    }

    /**
     * Get IP addresses that are not being used in any other table.
     *
     * @return array<int, array<int>>
     *
     * @throws Exception
     */
    public function getUnusedIpAddressesIds(int $limit): array
    {
        $prefix = MAUTIC_TABLE_PREFIX;

        $sql = <<<SQL
            SELECT {$prefix}ip_addresses.id FROM {$prefix}ip_addresses
                LEFT JOIN {$prefix}asset_downloads
                    ON {$prefix}asset_downloads.ip_id = {$prefix}ip_addresses.id
                LEFT JOIN {$prefix}campaign_lead_event_log
                    ON {$prefix}campaign_lead_event_log.ip_id = {$prefix}ip_addresses.id
                LEFT JOIN {$prefix}email_stats
                    ON {$prefix}email_stats.ip_id = {$prefix}ip_addresses.id
                LEFT JOIN {$prefix}email_stats_devices
                    ON {$prefix}email_stats_devices.ip_id = {$prefix}ip_addresses.id
                LEFT JOIN {$prefix}form_submissions
                    ON {$prefix}form_submissions.ip_id = {$prefix}ip_addresses.id
                LEFT JOIN {$prefix}lead_ips_xref
                    ON {$prefix}lead_ips_xref.ip_id = {$prefix}ip_addresses.id
                LEFT JOIN {$prefix}lead_points_change_log
                    ON {$prefix}lead_points_change_log.ip_id = {$prefix}ip_addresses.id
                LEFT JOIN {$prefix}page_hits
                    ON {$prefix}page_hits.ip_id = {$prefix}ip_addresses.id
                LEFT JOIN {$prefix}point_lead_action_log
                    ON {$prefix}point_lead_action_log.ip_id = {$prefix}ip_addresses.id
                LEFT JOIN {$prefix}point_lead_event_log
                    ON {$prefix}point_lead_event_log.ip_id = {$prefix}ip_addresses.id
                LEFT JOIN {$prefix}push_notification_stats
                    ON {$prefix}push_notification_stats.ip_id = {$prefix}ip_addresses.id
                LEFT JOIN {$prefix}sms_message_stats
                    ON {$prefix}sms_message_stats.ip_id = {$prefix}ip_addresses.id
                LEFT JOIN {$prefix}stage_lead_action_log
                    ON {$prefix}stage_lead_action_log.ip_id = {$prefix}ip_addresses.id
                LEFT JOIN {$prefix}video_hits
                    ON {$prefix}video_hits.ip_id = {$prefix}ip_addresses.id
            WHERE {$prefix}asset_downloads.id IS NULL
              AND {$prefix}campaign_lead_event_log.id IS NULL
              AND {$prefix}email_stats.id IS NULL
              AND {$prefix}email_stats_devices.id IS NULL
              AND {$prefix}form_submissions.id IS NULL
              AND {$prefix}lead_ips_xref.lead_id IS NULL
              AND {$prefix}lead_points_change_log.id IS NULL
              AND {$prefix}page_hits.id IS NULL
              AND {$prefix}point_lead_action_log.point_id IS NULL
              AND {$prefix}point_lead_event_log.event_id IS NULL
              AND {$prefix}push_notification_stats.id IS NULL
              AND {$prefix}sms_message_stats.id IS NULL
              AND {$prefix}stage_lead_action_log.stage_id IS NULL
              AND {$prefix}video_hits.id IS NULL
            LIMIT :limit
SQL;

        $params = ['limit' => $limit];
        $types  = ['limit' => \PDO::PARAM_INT];

        return $this->_em->getConnection()->executeQuery($sql, $params, $types)->fetchFirstColumn();
    }

    /**
     * @param array<int, array<int>> $ids
     *
     * @throws Exception
     */
    public function deleteUnusedIpAddresses(array $ids): int
    {
        $prefix    = MAUTIC_TABLE_PREFIX;
        $ids       = implode(',', $ids);
        $deleteSql = <<<SQL
                DELETE FROM {$prefix}ip_addresses WHERE {$prefix}ip_addresses.id IN ({$ids});
SQL;

        return $this->_em->getConnection()->executeStatement($deleteSql);
    }
}
