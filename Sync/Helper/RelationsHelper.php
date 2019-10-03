<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Helper;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\RelationDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\InternalIdNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;

class RelationsHelper
{
    private $mappingHelper;
    private $objectsToSynchronize = [];

    public function __construct(MappingHelper $mappingsHelper)
    {
        $this->mappingHelper = $mappingsHelper;
    }

    /**
     * @param MappingManualDAO $mappingManualDao
     * @param ReportDAO        $syncReport
     */
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

    /**
     * @return array
     */
    public function getObjectsToSynchronize(): array
    {
        return $this->objectsToSynchronize;
    }

    /**
     * @param MappingManualDAO $mappingManualDao
     * @param ReportDAO        $syncReport
     * @param RelationDAO      $relationObject
     *
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\FieldNotFoundException
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectDeletedException
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException
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
     * @param MappingManualDAO $mappingManualDao
     * @param RelationDAO      $relationObject
     * @param ObjectDAO        $relObjectDao
     *
     * @return int
     *
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
     * @param MappingManualDAO $mappingManualDao
     * @param string           $relObjectName
     * @param ObjectDAO        $objectDao
     *
     * @return ObjectDAO
     *
     * @throws ObjectNotFoundException
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectDeletedException
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException
     */
    private function findInternalObject(MappingManualDAO $mappingManualDao, string $relObjectName, ObjectDAO $objectDao): ObjectDAO
    {
        $internalObjectsName = $this->getInternalObjectName($mappingManualDao, $relObjectName);

        return $this->mappingHelper->findMauticObject($mappingManualDao, $internalObjectsName, $objectDao);
    }

    /**
     * @param int         $relObjectId
     * @param string      $relInternalType
     * @param RelationDAO $relationObject
     * @param ReportDAO   $syncReport
     *
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\FieldNotFoundException
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
     * @param MappingManualDAO $mappingManualDao
     * @param string           $relObjectName
     *
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
