<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync;

/**
 * Class SyncReportDAO
 * @package Mautic\PluginBundle\Model\Sync\DAO
 */
class SyncReportDAO
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
        $this->objects[$objectDAO->getEntity()][$objectDAO->getId()] = $objectDAO;

        return $this;
    }

    /**
     * @param ObjectChangeDAO $objectChangeDAO
     * @return $this
     */
    public function addObjectChange(ObjectChangeDAO $objectChangeDAO)
    {
        $this->objectsChanges[$objectChangeDAO->getEntity()][$objectChangeDAO->getId()] = $objectChangeDAO;

        return $this;
    }

    /**
     * @param string $entity
     * @param int    $entityId
     * @param string $field
     *
     * @return InformationChangeRequestDAO
     */
    public function getInformationChangeRequest($entity, $entityId, $field)
    {
        if (isset($this->objects[$entity][$entityId])) {
            /** @var ObjectDAO $object */
            $object = $this->objects[$entity][$entityId];
            $fieldValue = $object->getField($field);
            if ($field !== null) {
                return new InformationChangeRequestDAO($this->integration, $entity, $entityId, $field, $fieldValue);
            }
        }
        if (isset($this->objectsChanges[$entity][$entityId])) {
            /** @var ObjectChangeDAO $objectChange */
            $objectChange = $this->objectsChanges[$entity][$entityId];
            $fieldValue = $objectChange->getField($field);
            if ($fieldValue === null) {
                $fieldChange = $objectChange->getFieldChange($field);
                if($fieldChange !== null) {
                    $informationChangeRequest = new InformationChangeRequestDAO(
                        $this->integration,
                        $entity, $entityId,
                        $field,
                        $fieldChange->getValue()
                    );
                    return $informationChangeRequest->setPossibleChangeTimestamp($fieldChange->getPossibleChangeTimestamp())
                        ->setCertainChangeTimestamp($fieldChange->getCertainChangeTimestamp());
                }
            }
            else {
                $informationChangeRequest = new InformationChangeRequestDAO($this->integration, $entity, $entityId, $field, $fieldValue);
                return $informationChangeRequest->setPossibleChangeTimestamp($objectChange->getChangeTimestamp());
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
