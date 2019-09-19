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

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;

class RelationsHelper
{
    private $mappingHelper;
    private $objectsToSynchronize = [];

    public function __construct(MappingHelper $mappingsHelper)
    {
        $this->mappingHelper = $mappingsHelper;
    }

    public function processRelations(MappingManualDAO $mappingManualDao, ReportDAO $syncReport)
    {
        foreach ($syncReport->getRelations() as $objectRelations) {
            $this->processRelationsForObjectName($mappingManualDao, $objectRelations);
        }
    }

    private function processRelationsForObjectName(MappingManualDAO $mappingManualDao, $objectRelations)
    {
        foreach ($objectRelations as $objectRelation) {
            $this->processRelationsForObject($mappingManualDao, $objectRelation);
        }
    }

    private function processRelationsForObject(MappingManualDAO $mappingManualDao,  $objectRelation)
    {
        foreach ($objectRelation as $relationObject) {
            $relObjectDao = new ObjectDAO($relationObject->getRelObjectName(), $relationObject->getRelObjectIntegrationId());
            $relObjects = $this->findInternalObjects($mappingManualDao, $relationObject->getRelObjectName(), $relObjectDao);
            if (empty($relObjects)) {
                $this->objectsToSynchronize[] = $relObjectDao;
                continue;
            }
            $relationObject->setRelObjectInternalId($relObjects[0]->getObjectId());
        }
    }

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