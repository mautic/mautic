<?php

namespace Mautic\LeadBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<ListLead>
 */
class ListLeadRepository extends CommonRepository
{
    public const DELETE_BATCH_SIZE = 5000;

    /**
     * Updates lead ID (e.g. after a lead merge).
     */
    public function updateLead($fromLeadId, $toLeadId): void
    {
        // First check to ensure the $toLead doesn't already exist
        $results = $this->_em->getConnection()->createQueryBuilder()
            ->select('l.leadlist_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'l')
            ->where('l.lead_id = '.$toLeadId)
            ->executeQuery()
            ->fetchAllAssociative();

        $lists = [];
        foreach ($results as $r) {
            $lists[] = $r['leadlist_id'];
        }

        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'lead_lists_leads')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId);

        if (!empty($lists)) {
            $q->andWhere(
                $q->expr()->notIn('leadlist_id', $lists)
            )->executeStatement();

            // Delete remaining leads as the new lead already belongs
            $this->_em->getConnection()->createQueryBuilder()
                ->delete(MAUTIC_TABLE_PREFIX.'lead_lists_leads')
                ->where('lead_id = '.(int) $fromLeadId)
                ->executeStatement();
        } else {
            $q->executeStatement();
        }
    }

    /**
     * @param mixed[] $filters
     */
    public function getContactsCountBySegment(int $segmentId, array $filters = []): int
    {
        $qb = $this->createQueryBuilder('ll');
        $qb->select('count(ll.list) as count')
            ->where('ll.list = :segmentId')
            ->setParameter('segmentId', $segmentId);

        foreach ($filters as $colName => $val) {
            $entityFieldName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $colName))));
            $qb->andWhere(sprintf('ll.%s=:%s', $entityFieldName, $entityFieldName));
            $qb->setParameter($entityFieldName, $val);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function deleteAnonymousContacts(): int
    {
        $conn           = $this->getEntityManager()->getConnection();
        $tableName      = $this->getTableName();
        $leadsTableName = MAUTIC_TABLE_PREFIX . 'leads';
        $tempTableName  = 'to_delete';

        // Drop any leftover temporary table from previous runs (works on both MySQL and PostgreSQL)
        $conn->executeQuery("DROP TABLE IF EXISTS {$tempTableName}");

        // Create temporary table with the composite keys of rows to delete
        $createSql = "CREATE TEMPORARY TABLE {$tempTableName} AS
                  SELECT lll.leadlist_id, lll.lead_id
                  FROM {$tableName} lll
                  JOIN {$leadsTableName} l ON l.id = lll.lead_id
                  WHERE l.date_identified IS NULL";

        $conn->executeQuery($createSql);

        // Batched delete using composite key IN subquery (fully compatible with MySQL and PostgreSQL)
        $deleteSql = "DELETE FROM {$tableName}
                  WHERE (leadlist_id, lead_id) IN (
                      SELECT leadlist_id, lead_id
                      FROM {$tempTableName}
                      ORDER BY leadlist_id ASC, lead_id ASC
                      LIMIT " . self::DELETE_BATCH_SIZE . "
                  )";

        $deletedRecordCount = 0;
        $deletedRows        = true;

        while ($deletedRows > 0) {
            $deletedRows = $conn->executeStatement($deleteSql);
            $deletedRecordCount += $deletedRows;
        }

        // Clean up the temporary table
        $conn->executeQuery("DROP TABLE IF EXISTS {$tempTableName}");

        return $deletedRecordCount;
    }
}
