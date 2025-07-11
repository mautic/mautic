<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Report;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\RemappedObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\RelationsDAO;
use Mautic\IntegrationsBundle\Sync\Exception\FieldNotFoundException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;

class ReportDAO
{
    private array $objects = [];

    private array $remappedObjects = [];

    private RelationsDAO $relationsDAO;

    /**
     * @param string $integration
     */
    public function __construct(
        private $integration,
    ) {
        $this->relationsDAO    = new RelationsDAO();
    }

    /**
     * @return string
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    /**
     * @return $this
     */
    public function addObject(ObjectDAO $objectDAO)
    {
        if (!isset($this->objects[$objectDAO->getObject()])) {
            $this->objects[$objectDAO->getObject()] = [];
        }

        $this->objects[$objectDAO->getObject()][$objectDAO->getObjectId()] = $objectDAO;

        return $this;
    }

    /**
     * @param mixed  $oldObjectId
     * @param string $oldObjectName
     * @param string $newObjectName
     * @param mixed  $newObjectId
     */
    public function remapObject($oldObjectName, $oldObjectId, $newObjectName, $newObjectId = null): void
    {
        if (null === $newObjectId) {
            $newObjectId = $oldObjectId;
        }

        $this->remappedObjects[$oldObjectId] = new RemappedObjectDAO($this->integration, $oldObjectName, $oldObjectId, $newObjectName, $newObjectId);
    }

    /**
     * @throws ObjectNotFoundException
     * @throws FieldNotFoundException
     */
    public function getInformationChangeRequest($objectName, $objectId, $fieldName): InformationChangeRequestDAO
    {
        if (empty($this->objects[$objectName][$objectId])) {
            throw new ObjectNotFoundException($objectName.':'.$objectId);
        }

        /** @var ObjectDAO $reportObject */
        $reportObject             = $this->objects[$objectName][$objectId];
        $reportField              = $reportObject->getField($fieldName);
        $informationChangeRequest = new InformationChangeRequestDAO(
            $this->integration,
            $objectName,
            $objectId,
            $fieldName,
            $reportField->getValue()
        );

        $informationChangeRequest->setPossibleChangeDateTime($reportObject->getChangeDateTime())
            ->setCertainChangeDateTime($reportField->getChangeDateTime());

        return $informationChangeRequest;
    }

    /**
     * @return ObjectDAO[]
     */
    public function getObjects(?string $objectName)
    {
        $returnedObjects = [];
        if (null === $objectName) {
            foreach ($this->objects as $objects) {
                foreach ($objects as $object) {
                    $returnedObjects[] = $object;
                }
            }

            return $returnedObjects;
        }

        return $this->objects[$objectName] ?? [];
    }

    /**
     * @return RemappedObjectDAO[]
     */
    public function getRemappedObjects(): array
    {
        return $this->remappedObjects;
    }

    /**
     * @param int $objectId
     */
    public function getObject(string $objectName, $objectId): ?ObjectDAO
    {
        if (!isset($this->objects[$objectName])) {
            return null;
        }

        if (!isset($this->objects[$objectName][$objectId])) {
            return null;
        }

        return $this->objects[$objectName][$objectId];
    }

    public function shouldSync(): bool
    {
        return !empty($this->objects);
    }

    public function getRelations(): RelationsDAO
    {
        return $this->relationsDAO;
    }
}
