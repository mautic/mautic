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
        $leadsTableName = MAUTIC_TABLE_PREFIX.'leads';
        $tempTableName  = 'to_delete';

        // Drop any existing temporary table (safe for both MySQL/MariaDB and PostgreSQL)
        $conn->executeQuery("DROP TABLE IF EXISTS {$tempTableName}");

        // Create temporary table with the composite keys of anonymous contacts in lists
        $createSql = "CREATE TEMPORARY TABLE {$tempTableName} AS
                  SELECT lll.leadlist_id, lll.lead_id
                  FROM {$tableName} lll
                  JOIN {$leadsTableName} l ON l.id = lll.lead_id
                  WHERE l.date_identified IS NULL";

        $conn->executeQuery($createSql);

        $deletedRecordCount = 0;

        while (true) {
            // Fetch the next batch of keys (portable via QueryBuilder)
            $qb = $conn->createQueryBuilder();
            $qb->select('leadlist_id', 'lead_id')
                ->from($tempTableName)
                ->orderBy('leadlist_id', 'ASC')
                ->addOrderBy('lead_id', 'ASC')
                ->setMaxResults(self::DELETE_BATCH_SIZE);

            $batch = $qb->executeQuery()->fetchAllAssociative();

            if (empty($batch)) {
                break;
            }

            // Build dynamic IN clause with placeholders for the composite keys
            $placeholders = [];
            $params       = [];
            $types        = [];

            foreach ($batch as $row) {
                $placeholders[] = '(?, ?)';
                $params[]       = $row['leadlist_id'];
                $params[]       = $row['lead_id'];
                $types[]        = \Doctrine\DBAL\ParameterType::INTEGER;
                $types[]        = \Doctrine\DBAL\ParameterType::INTEGER;
            }

            $inClause = implode(', ', $placeholders);

            // Delete the batch from the main table
            $deleteMainSql = "DELETE FROM {$tableName}
                          WHERE (leadlist_id, lead_id) IN ({$inClause})";

            $deleted = $conn->executeStatement($deleteMainSql, $params, $types);
            $deletedRecordCount += $deleted;

            // Remove the processed batch from the temp table so the next iteration gets the next batch
            $deleteTempSql = "DELETE FROM {$tempTableName}
                          WHERE (leadlist_id, lead_id) IN ({$inClause})";

            $conn->executeStatement($deleteTempSql, $params, $types);
        }

        // Final cleanup
        $conn->executeQuery("DROP TABLE IF EXISTS {$tempTableName}");

        return $deletedRecordCount;
    }
}
