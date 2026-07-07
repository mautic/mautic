<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Service;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Entity\OptimisticLockInterface;
use Mautic\CoreBundle\Entity\OptimisticLockTrait;

final readonly class OptimisticLockService implements OptimisticLockServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function incrementVersion(OptimisticLockInterface $entity): void
    {
        $className     = $entity::class;
        $metadata      = $this->entityManager->getClassMetadata($className);
        $versionField  = $entity->getVersionField();
        $versionColumn = $metadata->fieldNames[$versionField] ?? null;
        $tableName     = $metadata->table['name'];

        if (null === $versionColumn) {
            throw new \LogicException(sprintf('Field "%s::$%s" is not mapped. Did you forget to do so? See "%s::addVersionField()"', $className, $versionField, OptimisticLockTrait::class));
        }

        if (!$identifierValues = $metadata->getIdentifierValues($entity)) {
            throw new \LogicException('Entity must have ID for incrementing its version field.');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            // Build WHERE clause for identifiers
            $whereConditions = implode(' AND ', array_map(
                fn (string $name): string => "{$name} = :{$name}",
                $metadata->getIdentifierFieldNames()
            ));

            // Select current version with lock (FOR UPDATE)
            $selectQb = $connection->createQueryBuilder();
            $selectQb->select($versionColumn)
                ->from($tableName)
                ->where($whereConditions)
                ->setParameters($identifierValues);

            $sql        = $selectQb->getSQL().' FOR UPDATE';
            $parameters = $selectQb->getParameters();
            $types      = $selectQb->getParameterTypes();

            $currentVersion = (int) $connection->fetchOne($sql, $parameters, $types);

            $newVersion = $currentVersion + 1;

            // Update to new version
            $updateQb = $connection->createQueryBuilder();
            $updateQb->update($tableName)
                ->set($versionColumn, ':newVersion')
                ->where($whereConditions)
                ->setParameters(['newVersion' => $newVersion] + $identifierValues)
                ->executeStatement();

            $connection->commit();

            $entity->setVersion($newVersion);
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function resetVersion(OptimisticLockInterface $entity): void
    {
        $this->buildUpdateQuery($entity, $versionColumn)
            ->set($versionColumn, (string) OptimisticLockInterface::INITIAL_VERSION)
            ->executeStatement();

        $entity->setVersion(OptimisticLockInterface::INITIAL_VERSION);
    }

    public function acquireLock(OptimisticLockInterface $entity, int $expectedVersion = OptimisticLockInterface::INITIAL_VERSION): bool
    {
        $this->incrementVersion($entity);

        return ++$expectedVersion === $entity->getVersion();
    }

    private function buildUpdateQuery(OptimisticLockInterface $entity, ?string &$versionColumn = null): QueryBuilder // @phpstan-ignore parameterByRef.unusedType
    {
        $className     = $entity::class;
        $metadata      = $this->entityManager->getClassMetadata($className);
        $versionField  = $entity->getVersionField();
        $versionColumn = $metadata->fieldNames[$versionField] ?? null;

        if (null === $versionColumn) {
            throw new \LogicException(sprintf('Field "%s::$%s" is not mapped. Did you forget to do so? See "%s::addVersionField()"', $className, $versionField, OptimisticLockTrait::class));
        }

        if (!$identifierValues = $metadata->getIdentifierValues($entity)) {
            throw new \LogicException('Entity must have ID for incrementing its version field.');
        }

        return $this->entityManager->getConnection()
            ->createQueryBuilder()
            ->update($metadata->table['name'])
            ->where(implode(' AND ', array_map(
                fn (string $name): string => "{$name} = :{$name}",
                $metadata->getIdentifierFieldNames(),
            )))
            ->setParameters($identifierValues);
    }
}
