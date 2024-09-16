<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Helper;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\RelationDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\Exception\InternalIdNotFoundException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;

class RelationsHelper
{
    private $mappingHelper;
    private $objectsToSynchronize = [];

    public function __construct(MappingHelper $mappingsHelper)
    {
        $this->mappingHelper = $mappingsHelper;
    }

    public function processRelations(MappingManualDAO $mappingManualDao, ReportDAO $syncReport): void
    {
        $this->objectsToSynchronize = [];
        foreach ($syncReport->getRelations() as $relationObject) {
            if (0 < $relationObject->getRelObjectInternalId()) {
                continue;
            }

            $this->processRelation($mappingManualDao, $syncReport, $relationObject);
        }
    }

    public function getObjectsToSynchronize(): array
    {
        return $this->objectsToSynchronize;
    }

    /**
     * @throws \Mautic\IntegrationsBundle\Sync\Exception\FieldNotFoundException
     * @throws \Mautic\IntegrationsBundle\Sync\Exception\ObjectDeletedException
     * @throws \Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException
     */
    private function processRelation(MappingManualDAO $mappingManualDao, ReportDAO $syncReport, RelationDAO $relationObject): void
    {
        $relObjectDao = new ObjectDAO($relationObject->getRelObjectName(), $relationObject->getRelObjectIntegrationId());

        try {
            $internalObjectName = $this->getInternalObjectName($mappingManualDao, $relationObject->getRelObjectName());
            $internalObjectId   = $this->getInternalObjectId($mappingManualDao, $relationObject, $relObjectDao);
            $this->addObjectInternalId($internalObjectId, $internalObjectName, $relationObject, $syncReport);
        } catch (ObjectNotFoundException $e) {
            return; // We are not mapping this object
        } catch (InternalIdNotFoundException  $e) {
            $this->objectsToSynchronize[] = $relObjectDao;
        }
    }

    /**
     * @throws InternalIdNotFoundException
     */
    private function getInternalObjectId(MappingManualDAO $mappingManualDao, RelationDAO $relationObject, ObjectDAO $relObjectDao): int
    {
        $relObject        = $this->findInternalObject($mappingManualDao, $relationObject->getRelObjectName(), $relObjectDao);
        $internalObjectId = (int) $relObject->getObjectId();

        if ($internalObjectId) {
            return $internalObjectId;
        }

        throw new InternalIdNotFoundException($relationObject->getRelObjectName());
    }

    /**
     * @throws ObjectNotFoundException
     * @throws \Mautic\IntegrationsBundle\Sync\Exception\ObjectDeletedException
     * @throws \Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException
     */
    private function findInternalObject(MappingManualDAO $mappingManualDao, string $relObjectName, ObjectDAO $objectDao): ObjectDAO
    {
        $internalObjectsName = $this->getInternalObjectName($mappingManualDao, $relObjectName);

        return $this->mappingHelper->findMauticObject($mappingManualDao, $internalObjectsName, $objectDao);
    }

    /**
     * @throws \Mautic\IntegrationsBundle\Sync\Exception\FieldNotFoundException
     */
    private function addObjectInternalId(int $relObjectId, string $relInternalType, RelationDAO $relationObject, ReportDAO $syncReport): void
    {
        $relationObject->setRelObjectInternalId($relObjectId);
        $objectDAO      = $syncReport->getObject($relationObject->getObjectName(), $relationObject->getObjectIntegrationId());
        $referenceValue = $objectDAO->getField($relationObject->getRelFieldName())->getValue()->getNormalizedValue();
        $referenceValue->setType($relInternalType);
        $referenceValue->setValue($relObjectId);
    }

    /**
     * @return mixed
     *
     * @throws ObjectNotFoundException
     */
    private function getInternalObjectName(MappingManualDAO $mappingManualDao, string $relObjectName)
    {
        $internalObjectsNames = $mappingManualDao->getMappedInternalObjectsNames($relObjectName);

        return $internalObjectsNames[0];
    }
}
