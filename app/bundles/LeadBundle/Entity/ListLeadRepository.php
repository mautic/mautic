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

    /**
     * Deletes anonymous contacts (leads where date_identified IS NULL) from lead list relations.
     *
     * @return int Number of deleted rows
     */
    public function deleteAnonymousContacts(): int
    {
        $conn           = $this->getEntityManager()->getConnection();
        $tableName      = $this->getTableName(); // lead_lists_leads
        $leadsTableName = MAUTIC_TABLE_PREFIX . 'leads';
        $tempTableName  = 'tmp_anon_delete';
    
        // Clean up any leftover temp table
        $conn->executeQuery("DROP TEMPORARY TABLE IF EXISTS {$tempTableName}");
    
        // Create temp table with rows to delete (leadlist_id + lead_id composite key)
        $conn->executeQuery("
            CREATE TEMPORARY TABLE {$tempTableName} (
                leadlist_id INT UNSIGNED NOT NULL,
                lead_id     INT UNSIGNED NOT NULL,
                PRIMARY KEY (leadlist_id, lead_id)
            ) ENGINE = MEMORY
            AS
            SELECT lll.leadlist_id, lll.lead_id
            FROM {$tableName} lll
            INNER JOIN {$leadsTableName} l ON l.id = lll.lead_id
            WHERE l.date_identified IS NULL
        ");
    
        $deletedRecordCount = 0;
    
        // Batch delete using JOIN (MariaDB + MySQL + PostgreSQL compatible)
        $deleteSql = "
            DELETE lll
            FROM {$tableName} lll
            INNER JOIN {$tempTableName} tmp
                ON lll.leadlist_id = tmp.leadlist_id
               AND lll.lead_id     = tmp.lead_id
            LIMIT " . self::DELETE_BATCH_SIZE;
    
        do {
            $deletedRows = $conn->executeStatement($deleteSql);
            $deletedRecordCount += $deletedRows;
        } while ($deletedRows > 0);
    
        // Cleanup
        $conn->executeQuery("DROP TEMPORARY TABLE IF EXISTS {$tempTableName}");
    
        return $deletedRecordCount;
    }
}
