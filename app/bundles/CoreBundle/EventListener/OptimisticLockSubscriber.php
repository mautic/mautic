<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Mautic\CoreBundle\Entity\OptimisticLockInterface;
use Mautic\CoreBundle\Entity\OptimisticLockTrait;

class OptimisticLockSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::postUpdate,
        ];
    }

    /**
     * If the object implements OptimisticLockInterface and is marked for incrementing the version, object's version column/field is incremented.
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        $object = $args->getObject();

        if (!$object instanceof OptimisticLockInterface || !$object->isMarkedForVersionIncrement()) {
            return;
        }

        $entityManager = $args->getObjectManager();

        if (!$entityManager instanceof EntityManagerInterface) {
            return;
        }

        $className     = $object::class;
        $metadata      = $entityManager->getClassMetadata($className);
        $versionField  = $object->getVersionField();
        $versionColumn = $metadata->fieldNames[$versionField] ?? null;

        if (null === $versionColumn) {
            throw new \LogicException(sprintf('Field "%s::$%s" is not mapped. Did you forget to do so? See "%s::addVersionField()"', $className, $versionField, OptimisticLockTrait::class));
        }

        $connection = $entityManager->getConnection();
        $connection->createQueryBuilder()
            ->update($metadata->table['name'])
            ->set($versionColumn, "(@newVersion := {$versionColumn} + 1)")
            ->where(implode(' AND ', array_map(function (string $name): string {
                return "{$name} = :{$name}";
            }, $metadata->getIdentifierFieldNames())))
            ->setParameters($entityManager->getUnitOfWork()->getEntityIdentifier($object))
            ->executeStatement();

        $newVersion = (int) $connection->executeQuery('SELECT @newVersion')->fetchOne();
        $object->setVersion($newVersion);
    }
}
