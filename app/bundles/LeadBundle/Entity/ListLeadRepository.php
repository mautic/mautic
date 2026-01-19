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
        $tableName      = $this->getTableName();
        $leadsTableName = MAUTIC_TABLE_PREFIX.'leads';
        $batchSize      = self::DELETE_BATCH_SIZE;

        $deletedRecordCount = 0;
        $offset             = 0;

        do {
            // Fetch next batch of composite keys (leadlist_id, lead_id)
            $selectSql = "
                SELECT lll.leadlist_id, lll.lead_id
                FROM {$tableName} lll
                INNER JOIN {$leadsTableName} l ON l.id = lll.lead_id
                WHERE l.date_identified IS NULL
                ORDER BY lll.leadlist_id ASC, lll.lead_id ASC
                LIMIT {$batchSize} OFFSET {$offset}
            ";

            $rows = $conn->executeQuery($selectSql)->fetchAllAssociative();

            if (empty($rows)) {
                break;
            }

            // Build IN clause with tuple values: (1,100), (1,101), ...
            $placeholders = [];
            $params       = [];
            $types        = [];

            foreach ($rows as $index => $row) {
                $placeholders[] = '(?, ?)';
                $params[]       = (int) $row['leadlist_id'];
                $params[]       = (int) $row['lead_id'];
                $types[]        = \PDO::PARAM_INT;
                $types[]        = \PDO::PARAM_INT;
            }

            $tupleList = implode(', ', $placeholders);

            // Delete this batch
            $deleteSql = "
                DELETE FROM {$tableName}
                WHERE (leadlist_id, lead_id) IN ({$tupleList})
            ";

            $deletedRows = $conn->executeStatement($deleteSql, $params, $types);
            $deletedRecordCount += $deletedRows;

            $offset += $batchSize;

            // Small sleep to reduce DB load in very large operations
            // usleep(100000); // 0.1s - uncomment if needed
        } while (count($rows) === $batchSize);

        return $deletedRecordCount;
    }
}
