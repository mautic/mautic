<?php
/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Helper;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
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
     * @param RelationsDAO     $relationsDAO
     */
    public function processRelations(MappingManualDAO $mappingManualDao, ReportDAO $syncReport)
    {
        $this->objectsToSynchronize = [];
        foreach ($syncReport->getRelations()->getRelations() as $objectRelations) {
            $this->processRelationsForObjectName($mappingManualDao, $objectRelations, $syncReport);
        }
    }

    /**
     * @return array
     */
    public function getObjectsToSynchronize()
    {
        return $this->objectsToSynchronize;
    }

    /**
     * @param MappingManualDAO $mappingManualDao
     * @param array            $objectRelations
     */
    private function processRelationsForObjectName(MappingManualDAO $mappingManualDao, array $objectRelations, $syncReport)
    {
        foreach ($objectRelations as $objectRelation) {
            $this->processRelationsForObject($mappingManualDao, $objectRelation, $syncReport);
        }
    }

    /**
     * @param MappingManualDAO $mappingManualDao
     * @param RelationDAO[]    $objectRelation
     */
    private function processRelationsForObject(MappingManualDAO $mappingManualDao, array $objectRelation, $syncReport)
    {
        foreach ($objectRelation as $relationObject) {
            if (0 < $relationObject->getRelObjectInternalId()) {
                continue;
            }

            $relObjectDao = new ObjectDAO($relationObject->getRelObjectName(), $relationObject->getRelObjectIntegrationId());
            try {
                $relObjects = $this->findInternalObjects($mappingManualDao, $relationObject->getRelObjectName(), $relObjectDao);
            }
            catch(ObjectNotFoundException $e) {
                // object shouldn't be synchronized
                return;
            }

            if (0 < $relObjects[0]->getObjectId()) {
                $relationObject->setRelObjectInternalId($relObjects[0]->getObjectId());
                $objectDAO = $syncReport->getObject($relationObject->getObjectName(), $relationObject->getObjectIntegrationId());
                $objectDAO->getField($relationObject->getRelFieldName())->getValue()->getNormalizedValue()->setValue($relObjects[0]->getObjectId());
                continue;
            }
            $this->objectsToSynchronize[] = $relObjectDao;
        }
    }

    /**
     * @param MappingManualDAO $mappingManualDao
     * @param string           $relObjectName
     * @param string           $objectDao
     *
     * @return array
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectDeletedException
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException
     */
    private function findInternalObjects(MappingManualDAO $mappingManualDao, $relObjectName, $objectDao)
    {
        $internalObjectsNames = $mappingManualDao->getMappedInternalObjectsNames($relObjectName);

        $objects = [];
        foreach($internalObjectsNames as $internalObjectsName) {
            $objects[] = $this->mappingHelper->findMauticObject($mappingManualDao, $internalObjectsName, $objectDao);
        }

        return $objects;
    }
}