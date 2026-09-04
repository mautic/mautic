<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
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

        if ([] !== $lists) {
            $q->andWhere(
                $q->expr()->notIn('leadlist_id', ':ids')
            )
                ->setParameter('ids', $lists, ArrayParameterType::INTEGER)
                ->executeStatement();

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
        $tempTableName  = 'to_delete';
        $platform       = $conn->getDatabasePlatform();

        // Drop temporary table (platform-safe)
        $conn->executeStatement(
            DatabasePlatform::getDropTemporaryTableSql($platform, $tempTableName, true)
        );

        $selectSql = sprintf(
            'SELECT lll.leadlist_id, lll.lead_id
            FROM %s lll
            JOIN %s l ON l.id = lll.lead_id
            WHERE l.date_identified IS NULL',
            $tableName,
            $leadsTableName
        );

        // Create temporary table with the select (platform-safe)
        $conn->executeStatement(
            DatabasePlatform::getCreateTemporaryTableSql($platform, $tempTableName, $selectSql)
        );

        // Build the delete query (platform-safe)
        $deleteQuery = DatabasePlatform::getDeleteAnonymousContactsUsingTempTableSql(
            $platform,
            $tableName,
            $tempTableName,
            ['leadlist_id', 'lead_id'],
            self::DELETE_BATCH_SIZE
        );

        $deletedRecordCount= 0;
        while ($deletedRows = $conn->executeQuery($deleteQuery)->rowCount()) {
            $deletedRecordCount += $deletedRows;
        }

        return $deletedRecordCount;
    }

    public function removeLeadsByListId(int $listId): void
    {
        $tableName   = MAUTIC_TABLE_PREFIX.'lead_lists_leads';
        $conn        = $this->getEntityManager()->getConnection();
        $listIdParam = 'listId';

        $deleteQuery = DatabasePlatform::getDeleteByListIdSql(
            $conn->getDatabasePlatform(),
            $tableName,
            self::DELETE_BATCH_SIZE,
            ':'.$listIdParam,
        );

        do {
            $deletedRows = $conn->executeStatement(
                $deleteQuery,
                [$listIdParam => $listId]
            );
        } while ($deletedRows > 0);
    }
}
