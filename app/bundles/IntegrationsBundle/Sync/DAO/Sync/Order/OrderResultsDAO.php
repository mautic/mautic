<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Order;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\RemappedObjectDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;

class OrderResultsDAO
{
    /**
     * @var ObjectMapping[][]
     */
    private array $newObjectMappings = [];

    /**
     * @var ObjectMapping[][]
     */
    private array $updatedObjectMappings = [];

    /**
     * @var RemappedObjectDAO[][]
     */
    private array $remappedObjects = [];

    /**
     * @var ObjectChangeDAO[][]
     */
    private array $deletedObjects = [];

    /**
     * @param ObjectMapping[]     $newObjectMappings
     * @param ObjectMapping[]     $updatedObjectMappings
     * @param RemappedObjectDAO[] $remappedObjects
     * @param ObjectChangeDAO[]   $deletedObjects
     */
    public function __construct(array $newObjectMappings, array $updatedObjectMappings, array $remappedObjects, array $deletedObjects)
    {
        $this->groupNewObjectMappingsByObjectName($newObjectMappings);
        $this->groupUpdatedObjectMappingsByObjectName($updatedObjectMappings);
        $this->groupRemappedObjectsByObjectName($remappedObjects);
        $this->groupDeletedObjectsByObjectName($deletedObjects);
    }

    /**
     * @return ObjectMapping[]
     */
    public function getObjectMappings(string $objectName): array
    {
        $newObjectMappings     = $this->newObjectMappings[$objectName] ?? [];
        $updatedObjectMappings = $this->updatedObjectMappings[$objectName] ?? [];

        return array_merge($newObjectMappings, $updatedObjectMappings);
    }

    /**
     * @return ObjectMapping[]
     *
     * @throws ObjectNotFoundException
     */
    public function getNewObjectMappings(string $objectName): array
    {
        if (!isset($this->newObjectMappings[$objectName])) {
            throw new ObjectNotFoundException($objectName);
        }

        return $this->newObjectMappings[$objectName];
    }

    /**
     * @return ObjectMapping[]
     *
     * @throws ObjectNotFoundException
     */
    public function getUpdatedObjectMappings(string $objectName): array
    {
        if (!isset($this->updatedObjectMappings[$objectName])) {
            throw new ObjectNotFoundException($objectName);
        }

        return $this->updatedObjectMappings[$objectName];
    }

    /**
     * @return RemappedObjectDAO[]
     *
     * @throws ObjectNotFoundException
     */
    public function getRemappedObjects(string $objectName): array
    {
        if (!isset($this->remappedObjects[$objectName])) {
            throw new ObjectNotFoundException($objectName);
        }

        return $this->remappedObjects[$objectName];
    }

    /**
     * @return ObjectChangeDAO[]
     *
     * @throws ObjectNotFoundException
     */
    public function getDeletedObjects(string $objectName): array
    {
        if (!isset($this->deletedObjects[$objectName])) {
            throw new ObjectNotFoundException($objectName);
        }

        return $this->deletedObjects[$objectName];
    }

    /**
     * @param ObjectMapping[] $objectMappings
     */
    private function groupNewObjectMappingsByObjectName(array $objectMappings): void
    {
        foreach ($objectMappings as $objectMapping) {
            if (!isset($this->newObjectMappings[$objectMapping->getIntegrationObjectName()])) {
                $this->newObjectMappings[$objectMapping->getIntegrationObjectName()] = [];
            }

            $this->newObjectMappings[$objectMapping->getIntegrationObjectName()][] = $objectMapping;
        }
    }

    /**
     * @param ObjectMapping[] $objectMappings
     */
    private function groupUpdatedObjectMappingsByObjectName(array $objectMappings): void
    {
        foreach ($objectMappings as $objectMapping) {
            if (!isset($this->updatedObjectMappings[$objectMapping->getIntegrationObjectName()])) {
                $this->updatedObjectMappings[$objectMapping->getIntegrationObjectName()] = [];
            }

            $this->updatedObjectMappings[$objectMapping->getIntegrationObjectName()][] = $objectMapping;
        }
    }

    /**
     * @param RemappedObjectDAO[] $remappedObjects
     */
    private function groupRemappedObjectsByObjectName(array $remappedObjects): void
    {
        foreach ($remappedObjects as $remappedObject) {
            if (!isset($this->remappedObjects[$remappedObject->getNewObjectName()])) {
                $this->remappedObjects[$remappedObject->getNewObjectName()] = [];
            }

            $this->remappedObjects[$remappedObject->getNewObjectName()][] = $remappedObject;
        }
    }

    /**
     * @param ObjectChangeDAO[] $deletedObjects
     */
    private function groupDeletedObjectsByObjectName(array $deletedObjects): void
    {
        foreach ($deletedObjects as $deletedObject) {
            if (!isset($this->deletedObjects[$deletedObject->getObject()])) {
                $this->deletedObjects[$deletedObject->getObject()] = [];
            }

            $this->deletedObjects[$deletedObject->getObject()][] = $deletedObject;
        }
    }
}
