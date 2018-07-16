<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report;

use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\InformationChangeRequestDAO;

/**
 * Class SyncReportDAO
 * @package Mautic\PluginBundle\Model\Sync\DAO
 */
class ReportDAO
{
    /**
     * @var string
     */
    private $integration;

    /**
     * @var ObjectDAO[]
     */
    private $objects = [];

    /**
     * @var ObjectChangeDAO[]
     */
    private $objectsChanges = [];

    /**
     * SyncReportDAO constructor.
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
     * @return $this
     */
    public function addObject(ObjectDAO $objectDAO)
    {
        $this->objects[$objectDAO->getObject()][$objectDAO->getObjectId()] = $objectDAO;

        return $this;
    }

    /**
     * @param ObjectChangeDAO $objectChangeDAO
     * @return $this
     */
    public function addObjectChange(ObjectChangeDAO $objectChangeDAO)
    {
        $this->objectsChanges[$objectChangeDAO->getObject()][$objectChangeDAO->getObjectId()] = $objectChangeDAO;

        return $this;
    }

    /**
     * @param string $objectName
     * @param int    $objectId
     * @param string $fieldName
     *
     * @return InformationChangeRequestDAO
     */
    public function getInformationChangeRequest($objectName, $objectId, $fieldName)
    {
        if (isset($this->objectsChanges[$objectName][$objectId])) {
            /** @var ObjectChangeDAO $objectChange */
            $objectChange = $this->objectsChanges[$objectName][$objectId];
            $fieldValue = $objectChange->getField($fieldName);
            if ($fieldValue === null) {
                $fieldChange = $objectChange->getFieldChange($fieldName);
                if($fieldChange !== null) {
                    $informationChangeRequest = new InformationChangeRequestDAO(
                        $this->integration,
                        $objectName,
                        $objectId,
                        $fieldName,
                        $fieldChange->getValue()
                    );
                    return $informationChangeRequest->setPossibleChangeTimestamp($objectChange->getChangeTimestamp())
                        ->setCertainChangeTimestamp($fieldChange->getChangeTimestamp());
                }
            }
            else {
                $informationChangeRequest = new InformationChangeRequestDAO($this->integration, $objectName, $objectId, $fieldName, $fieldValue);
                return $informationChangeRequest->setPossibleChangeTimestamp($objectChange->getChangeTimestamp());
            }
        }
        if (isset($this->objects[$objectName][$objectId])) {
            /** @var ObjectDAO $object */
            $object = $this->objects[$objectName][$objectId];
            $fieldValue = $object->getField($fieldName);
            if ($fieldName !== null) {
                return new InformationChangeRequestDAO($this->integration, $objectName, $objectId, $fieldName, $fieldValue);
            }
        }
        return null;
    }

    /**
     * @return ObjectDAO[]
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * @return array
     */
    public function getObjectsChanges()
    {
        return $this->objectsChanges;
    }
}
