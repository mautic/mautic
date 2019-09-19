<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\RemappedObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\FieldNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;

/**
 * Class ReportDAO
 */
class ReportDAO
{
    /**
     * @var string
     */
    private $integration;

    /**
     * @var array
     */
    private $objects = [];

    /**
     * @var array
     */
    private $remappedObjects = [];

    /**
     * @var array
     */
    private $relations = [];

    /**
     * SyncReportDAO constructor.
     *
     * @param $integration
     */
    public function __construct($integration)
    {
        $this->integration = $integration;
    }

    /**
     * @return string
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    /**
     * @param ObjectDAO $objectDAO
     *
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
    public function remapObject($oldObjectName, $oldObjectId, $newObjectName, $newObjectId = null)
    {
        if (null === $newObjectId) {
            $newObjectId = $oldObjectId;
        }

        $this->remappedObjects[$oldObjectId] = new RemappedObjectDAO($this->integration, $oldObjectName, $oldObjectId, $newObjectName, $newObjectId);
    }

    /**
     * @param $objectName
     * @param $objectId
     * @param $fieldName
     *
     * @return InformationChangeRequestDAO
     * @throws ObjectNotFoundException
     * @throws FieldNotFoundException
     */
    public function getInformationChangeRequest($objectName, $objectId, $fieldName)
    {
        if (empty($this->objects[$objectName][$objectId])) {
            throw new ObjectNotFoundException($objectName.":".$objectId);
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
     * @param string|null $objectName
     *
     * @return ObjectDAO[]
     */
    public function getObjects(?string $objectName)
    {
        $returnedObjects = [];
        if (null === $objectName) {
            foreach ($this->objects as $objectName => $objects) {
                foreach ($objects as $object) {
                    $returnedObjects[] = $object;
                }
            }

            return $returnedObjects;
        }

        return isset($this->objects[$objectName]) ? $this->objects[$objectName] : [];
    }

    /**
     * @return RemappedObjectDAO[]
     */
    public function getRemappedObjects(): array
    {
        return $this->remappedObjects;
    }

    /**
     * @param string $objectName
     * @param int    $objectId
     *
     * @return ObjectDAO|null
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

    /**
     * @return bool
     */
    public function shouldSync()
    {
        return !empty($this->objects);
    }

    /**
     * @param ObjectDAO $objectDAO
     * @param array     $relations
     */
    public function addRelations(ObjectDAO $objectDAO, array $relations)
    {
        foreach ($relations as $relObjectName => $relation) {
            $this->addRelation($objectDAO, $relObjectName, $relation);
        }
    }

    /**
     * @param ObjectDAO $objectDAO
     * @param string    $relObjectName
     * @param string    $relObjectId
     */
    public function addRelation(ObjectDAO $objectDAO, string $fieldName, RelationDao $relation)
    {
        $this->relations[$objectDAO->getObject()][$objectDAO->getObjectId()][$fieldName] = $relation;
    }

    /**
     * @param string $objectName
     * @param string $objectId
     *
     * @return array
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * @param string $objectName
     * @param string $objectId
     *
     * @return array
     */
    public function getRelationsForObject(string $objectName, string $objectId): array
    {
        return $this->relations[$objectName][$objectId] ?? [];
    }


    /**
     * @param string $objectName
     * @param string $objectId
     * @param string $fieldName
     *
     * @return RelationDAO
     */
    public function getRelationsForField(string $objectName, string $objectId, string $fieldName): ?RelationDAO
    {
        return $this->relations[$objectName][$objectId][$fieldName] ?? null;
    }
}
