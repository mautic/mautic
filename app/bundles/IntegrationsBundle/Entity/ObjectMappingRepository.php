<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * @extends CommonRepository<ObjectMapping>
 */
class ObjectMappingRepository extends CommonRepository
{
    public function getInternalObject($integration, $integrationObjectName, $integrationObjectId, $internalObjectName): ?array
    {
        return $this->doGetInternalObject($integration, $integrationObjectName, $integrationObjectId, $internalObjectName);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getInternalObjectWithLock(string $integration, string $integrationObjectName, string $integrationObjectId, string $internalObjectName, string $lock = 'LOCK IN SHARE MODE'): ?array
    {
        return $this->doGetInternalObject($integration, $integrationObjectName, $integrationObjectId, $internalObjectName, $lock);
    }

    /**
     * @return array|null
     */
    public function getIntegrationObject($integration, $internalObjectName, $internalObjectId, $integrationObjectName)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'sync_object_mapping', 'i')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('i.integration', ':integration'),
                    $qb->expr()->eq('i.internal_object_name', ':internalObjectName'),
                    $qb->expr()->eq('i.internal_object_id', ':internalObjectId'),
                    $qb->expr()->eq('i.integration_object_name', ':integrationObjectName')
                )
            )
            ->setParameter('integration', $integration)
            ->setParameter('internalObjectName', $internalObjectName)
            ->setParameter('internalObjectId', $internalObjectId)
            ->setParameter('integrationObjectName', $integrationObjectName);

        $result = $qb->executeQuery()->fetchAssociative();

        return $result ?: null;
    }

    /**
     * @param string $integration
     * @param string $oldObjectName
     * @param mixed  $oldObjectId
     * @param string $newObjectName
     * @param mixed  $newObjectId
     */
    public function updateIntegrationObject($integration, $oldObjectName, $oldObjectId, $newObjectName, $newObjectId): int
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->update(MAUTIC_TABLE_PREFIX.'sync_object_mapping', 'i')
            ->set('integration_object_name', ':newObjectName')
            ->set('integration_object_id', ':newObjectId')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('i.integration', ':integration'),
                    $qb->expr()->eq('i.integration_object_name', ':oldObjectName'),
                    $qb->expr()->eq('i.integration_object_id', ':oldObjectId')
                )
            )
            ->setParameter('newObjectName', $newObjectName)
            ->setParameter('newObjectId', $newObjectId)
            ->setParameter('integration', $integration)
            ->setParameter('oldObjectName', $oldObjectName)
            ->setParameter('oldObjectId', $oldObjectId);

        return $qb->executeStatement();
    }

    public function updateInternalObjectId(int $internalObjectId, int $id): int
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->update(MAUTIC_TABLE_PREFIX.'sync_object_mapping')
            ->set('internal_object_id', ':internalObjectId')
            ->where($qb->expr()->eq('id', ':id'))
            ->setParameter('internalObjectId', $internalObjectId)
            ->setParameter('id', $id);

        return $qb->executeStatement();
    }

    /**
     * This method allows inserting a new record when an ORM way is not possible.
     * For example, when coping with \Doctrine\DBAL\Exception\RetryableException.
     */
    public function insert(string $integration, string $integrationObjectName, string $integrationObjectId, string $internalObjectName, int $internalObjectId, \DateTimeInterface $createdAt = null): int
    {
        $createdAt = $createdAt ?: new \DateTimeImmutable();
        $qb        = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->insert(MAUTIC_TABLE_PREFIX.'sync_object_mapping')
            ->values([
                'integration'             => ':integration',
                'integration_object_name' => ':integrationObjectName',
                'integration_object_id'   => ':integrationObjectId',
                'internal_object_name'    => ':internalObjectName',
                'internal_object_id'      => ':internalObjectId',
                'date_created'            => ':date',
                'last_sync_date'          => ':date',
                'is_deleted'              => ':isDeleted',
                'internal_storage'        => ':internalStorage',
            ])
            ->setParameter('integration', $integration)
            ->setParameter('integrationObjectName', $integrationObjectName)
            ->setParameter('integrationObjectId', $integrationObjectId)
            ->setParameter('internalObjectName', $internalObjectName)
            ->setParameter('internalObjectId', $internalObjectId)
            ->setParameter('date', $createdAt->format(DateTimeHelper::FORMAT_DB))
            ->setParameter('isDeleted', [], Types::BOOLEAN)
            ->setParameter('internalStorage', [], Types::JSON);

        return $qb->executeStatement();
    }

    /**
     * @param string[]|string $objectIds
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function markAsDeleted(string $integration, string $objectName, $objectIds): int
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->update(MAUTIC_TABLE_PREFIX.'sync_object_mapping', 'm')
            ->set('is_deleted', 1)
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('m.integration', ':integration'),
                    $qb->expr()->eq('m.integration_object_name', ':objectName')
                )
            )
            ->setParameter('integration', $integration)
            ->setParameter('objectName', $objectName);

        if (is_array($objectIds)) {
            $qb->setParameter('objectId', $objectIds, ArrayParameterType::STRING);
            $qb->andWhere($qb->expr()->in('m.integration_object_id', ':objectId'));
        } else {
            $qb->setParameter('objectId', $objectIds);
            $qb->andWhere($qb->expr()->eq('m.integration_object_id', ':objectId'));
        }

        return $qb->executeStatement();
    }

    public function deleteEntitiesForObject(int $internalObjectId, string $internalObject): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->delete(ObjectMapping::class, 'm');
        $qb->where('m.internalObjectName = :internalObject');
        $qb->andWhere('m.internalObjectId = :internalObjectId');
        $qb->setParameter('internalObject', $internalObject);
        $qb->setParameter('internalObjectId', $internalObjectId);
        $qb->getQuery()->execute();
    }

    /**
     * @return ObjectMapping[]
     */
    public function getIntegrationMappingsForInternalObject(string $internalObject, int $internalObjectId): array
    {
        $qb = $this->createQueryBuilder('m');
        $qb->select('m')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('m.internalObjectName', ':internalObject'),
                    $qb->expr()->eq('m.internalObjectId', ':internalObjectId')
                )
            )
            ->setParameter('internalObject', $internalObject)
            ->setParameter('internalObjectId', $internalObjectId);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $integration
     * @param string $integrationObjectName
     * @param string $integrationObjectId
     * @param string $internalObjectName
     *
     * @return mixed[]|null
     */
    private function doGetInternalObject($integration, $integrationObjectName, $integrationObjectId, $internalObjectName, string $lock = null): ?array
    {
        $connection = $this->getEntityManager()->getConnection();
        $qb         = $connection->createQueryBuilder();
        $qb->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'sync_object_mapping', 'i')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('i.integration', ':integration'),
                    $qb->expr()->eq('i.integration_object_name', ':integrationObjectName'),
                    $qb->expr()->eq('i.integration_object_id', ':integrationObjectId'),
                    $qb->expr()->eq('i.internal_object_name', ':internalObjectName')
                )
            )
            ->setParameter('integration', $integration)
            ->setParameter('integrationObjectName', $integrationObjectName)
            ->setParameter('integrationObjectId', $integrationObjectId)
            ->setParameter('internalObjectName', $internalObjectName);

        $lock   = $lock ? ' '.$lock : '';
        $result = $connection->executeQuery($qb->getSQL().$lock, $qb->getParameters(), $qb->getParameterTypes())->fetchAssociative();

        return $result ?: null;
    }
}
