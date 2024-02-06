<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<FieldChange>
 */
class FieldChangeRepository extends CommonRepository
{
    /**
     * Takes an object id & type and deletes all entities
     * that match the given column names.
     */
    public function deleteEntitiesForObjectByColumnName(int $objectId, string $objectType, array $columnNames): void
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb
            ->delete(MAUTIC_TABLE_PREFIX.'sync_object_field_change_report')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('object_type', ':objectType'),
                    $qb->expr()->eq('object_id', ':objectId'),
                    $qb->expr()->in('column_name', ':columnNames')
                )
            )
            ->setParameter('objectType', $objectType)
            ->setParameter('objectId', $objectId)
            ->setParameter('columnNames', $columnNames, ArrayParameterType::STRING)
            ->executeStatement();
    }

    /**
     * Takes an object id & type and deletes all entities that match.
     */
    public function deleteEntitiesForObject(int $objectId, string $objectType, ?string $integration = null): void
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $expr = CompositeExpression::and($qb->expr()->eq('object_type', ':objectType'), $qb->expr()->eq('object_id', ':objectId'));
        if ($integration) {
            $expr = $expr->with(
                $qb->expr()->eq('integration', ':integration')
            );
            $qb->setParameter('integration', $integration);
        }

        $qb->setParameter('objectType', $objectType)
            ->setParameter('objectId', (int) $objectId);

        $qb
            ->delete(MAUTIC_TABLE_PREFIX.'sync_object_field_change_report')
            ->where($expr)
            ->executeStatement();
    }

    /**
     * @param int|null $afterObjectId
     * @param int      $objectCount
     */
    public function findChangesBefore(string $integration, string $objectType, \DateTimeInterface $toDateTime, $afterObjectId = null, $objectCount = 100): array
    {
        // Get a list of object IDs so that we can get complete snapshots of the objects
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb
            ->select('f.object_id')
            ->from(MAUTIC_TABLE_PREFIX.'sync_object_field_change_report', 'f')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('f.integration', ':integration'),
                    $qb->expr()->eq('f.object_type', ':objectType'),
                    $qb->expr()->lte('f.modified_at', ':toDateTime')
                )
            )
            ->setParameter('integration', $integration)
            ->setParameter('objectType', $objectType)
            ->setParameter('toDateTime', $toDateTime->format('Y-m-d H:i:s'))
            ->orderBy('f.object_id')
            ->groupBy('f.object_id')
            ->setMaxResults($objectCount);

        if ($afterObjectId) {
            $qb->andWhere(
                $qb->expr()->gt('f.object_id', (int) $afterObjectId)
            );
        }

        $results = $qb->executeQuery()->fetchAllAssociative();

        $objectIds = [];
        foreach ($results as $result) {
            $objectIds[] = (int) $result['object_id'];
        }

        if (!$objectIds) {
            return [];
        }

        // Get all the field changes for the requested objects objects
        $qb
            ->resetQueryParts()
            ->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'sync_object_field_change_report', 'f')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('f.integration', ':integration'),
                    $qb->expr()->eq('f.object_type', ':objectType'),
                    $qb->expr()->in('f.object_id', $objectIds)
                )
            )
            ->setParameter('integration', $integration)
            ->setParameter('objectType', $objectType)
            ->orderBy('f.modified_at'); // Newer updated fields must override older updated fields

        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param int $objectId
     */
    public function findChangesForObject(string $integration, string $objectType, $objectId): array
    {
        // Get a list of object IDs so that we can get complete snapshots of the objects
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb
            ->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'sync_object_field_change_report', 'f')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('f.integration', ':integration'),
                    $qb->expr()->eq('f.object_type', ':objectType'),
                    $qb->expr()->eq('f.object_id', ':objectId')
                )
            )
            ->setParameter('integration', $integration)
            ->setParameter('objectType', $objectType)
            ->setParameter('objectId', (int) $objectId)
            ->orderBy('f.modified_at'); // Newer updated fields must override older updated fields

        return $qb->executeQuery()->fetchAllAssociative();
    }
}
