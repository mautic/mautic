<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
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
        $isPg           = $conn->getDatabasePlatform() instanceof PostgreSQLPlatform;
        $tableName      = $this->getTableName();
        $leadsTableName = MAUTIC_TABLE_PREFIX.'leads';
        $tempTableName  = 'to_delete';
        $conn->executeQuery(sprintf('DROP TABLE IF EXISTS %s', $tempTableName));

        // PostgreSQL requires "AS SELECT" for table creation
        $asSelect = $isPg ? 'AS ' : '';
        $conn->executeQuery(sprintf(
            'CREATE TEMPORARY TABLE %s %s SELECT lll.leadlist_id, lll.lead_id FROM %s lll JOIN %s l ON l.id = lll.lead_id WHERE l.date_identified IS NULL',
            $tempTableName,
            $asSelect,
            $tableName,
            $leadsTableName
        ));

        $deletedRecordCount = 0;

        do {
            if ($isPg) {
                // PostgreSQL: DELETE FROM ... USING
                $deleteQuery = sprintf(
                    'DELETE FROM %s lll 
             USING (SELECT leadlist_id, lead_id FROM %s LIMIT %d) d 
             WHERE lll.leadlist_id = d.leadlist_id AND lll.lead_id = d.lead_id',
                    $tableName,
                    $tempTableName,
                    self::DELETE_BATCH_SIZE
                );
            } else {
                // MySQL/MariaDB: DELETE lll FROM ... JOIN
                $deleteQuery = sprintf(
                    'DELETE lll FROM %s lll JOIN (SELECT leadlist_id, lead_id FROM %s LIMIT %d) d USING (leadlist_id, lead_id)',
                    $tableName,
                    $tempTableName,
                    self::DELETE_BATCH_SIZE
                );
            }

            $deletedRows = $conn->executeStatement($deleteQuery);
            $deletedRecordCount += $deletedRows;

            // Cleanup the temporary table for the next iteration to prevent infinite loops
            // or re-deleting the same rows if the temporary table is large
            if ($deletedRows > 0) {
                $conn->executeStatement(sprintf(
                    'DELETE FROM %s WHERE (leadlist_id, lead_id) IN (SELECT leadlist_id, lead_id FROM %s LIMIT %d)',
                    $tempTableName,
                    $tempTableName,
                    self::DELETE_BATCH_SIZE
                ));
            }
        } while ($deletedRows > 0);

        return $deletedRecordCount;
    }
}
