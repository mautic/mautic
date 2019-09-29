<?php
/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Helper;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\RelationDAO;

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
    public function processRelations(MappingManualDAO $mappingManualDao, ReportDAO $syncReport)
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
    private function processRelation(MappingManualDAO $mappingManualDao, ReportDAO $syncReport, RelationDAO $relationObject)
    {
        $relObjectDao = new ObjectDAO($relationObject->getRelObjectName(), $relationObject->getRelObjectIntegrationId());

        try {
            $relObjects = $this->findInternalObjects($mappingManualDao, $relationObject->getRelObjectName(), $relObjectDao);
            $this->addObjectInternalId($relObjects[0]->getObjectId(), $relationObject, $syncReport);
        } catch (ObjectNotFoundException $e) {
            $this->objectsToSynchronize[] = $relObjectDao;
        }
    }

    /**
     * @param MappingManualDAO $mappingManualDao
     * @param string           $relObjectName
     * @param ObjectDAO        $objectDao
     *
     * @return array
     * @throws ObjectNotFoundException
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectDeletedException
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException
     */
    private function findInternalObjects(MappingManualDAO $mappingManualDao, string $relObjectName, ObjectDAO $objectDao): array
    {
        $internalObjectsNames = $mappingManualDao->getMappedInternalObjectsNames($relObjectName);

        $objects = [];
        foreach($internalObjectsNames as $internalObjectsName) {
            $objects[] = $this->mappingHelper->findMauticObject($mappingManualDao, $internalObjectsName, $objectDao);
        }

        return $objects;
    }

    /**
     * @param int         $relObjectId
     * @param RelationDAO $relationObject
     * @param ReportDAO   $syncReport
     *
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\FieldNotFoundException
     */
    private function addObjectInternalId(int $relObjectId, RelationDAO $relationObject, ReportDAO $syncReport)
    {
        $relationObject->setRelObjectInternalId($relObjectId);
        $objectDAO = $syncReport->getObject($relationObject->getObjectName(), $relationObject->getObjectIntegrationId());
        $objectDAO->getField($relationObject->getRelFieldName())->getValue()->getNormalizedValue()->setValue($relObjectId);
    }
}