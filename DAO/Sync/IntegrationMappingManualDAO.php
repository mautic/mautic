<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync;

/**
 * Class IntegrationMappingManualDAO
 * @package Mautic\PluginBundle\Model\Sync\DAO
 */
class IntegrationMappingManualDAO
{
    /**
     * @var string
     */
    private $integration;

    /**
     * @var IntegrationEntityMappingDAO[]
     */
    private $entityMappings = [];

    /**
     * @var IntegrationFieldMappingDAO[]
     */
    private $fieldMappings = [];

    /**
     * @var array
     */
    private $internalEntityMapping = [];

    /**
     * @var array
     */
    private $integrationEntityMapping = [];

    /**
     * @var array
     */
    private $internalFieldMapping = [];

    /**
     * @var array
     */
    private $integrationFieldMapping = [];

    /**
     * @var int
     */
    private $entityMappingIndex = 0;

    /**
     * @var int
     */
    private $fieldMappingIndex = 0;

    /**
     * IntegrationMappingManualDAO constructor.
     * @param string $integration
     */
    public function __construct($integration)
    {
        $this->integration = $integration;
    }

    /**
     * @param IntegrationEntityMappingDAO $integrationEntityMapping
     */
    public function addEntityMapping(IntegrationEntityMappingDAO $integrationEntityMapping)
    {
        $this->entityMappings[$this->entityMappingIndex] = $integrationEntityMapping;

        $internalEntity = $integrationEntityMapping->getInternalEntity();
        $internalEntityId = $integrationEntityMapping->getInternalEntityId();
        $integrationEntity = $integrationEntityMapping->getIntegrationEntity();
        $integrationEntityId = $integrationEntityMapping->getIntegrationEntityId();
        $this->internalEntityMapping[$internalEntity][$internalEntityId] = $this->entityMappingIndex;
        $this->integrationEntityMapping[$integrationEntity][$integrationEntityId] = $this->entityMappingIndex;

        $this->entityMappingIndex += 1;
    }

    /**
     * @param IntegrationFieldMappingDAO $integrationFieldMapping
     */
    public function addFieldMapping(IntegrationFieldMappingDAO $integrationFieldMapping)
    {
        $this->fieldMappings[$this->fieldMappingIndex] = $integrationFieldMapping;

        $internalEntity = $integrationFieldMapping->getInternalEntity();
        $internalField = $integrationFieldMapping->getInternalField();
        $integrationEntity = $integrationFieldMapping->getIntegrationEntity();
        $integrationField = $integrationFieldMapping->getIntegrationField();
        $this->internalFieldMapping[$internalEntity][$internalField] = $this->fieldMappingIndex;
        $this->integrationFieldMapping[$integrationEntity][$integrationField] = $this->fieldMappingIndex;

        $this->fieldMappingIndex += 1;
    }

    /**
     * return string[]
     */
    public function getInternalEntities()
    {
        $entities = [];
        foreach($this->internalEntityMapping as $entity => $ids) {
            $entities[] = $entity;
        }
        return $entities;
    }

    /**
     * @param string $entity
     *
     * @return int[]
     */
    public function getInternalEntityIds($entity)
    {
        $ids = [];
        /** @var array $internalEntity */
        $internalEntity = $this->internalEntityMapping[$entity];
        foreach($internalEntity as $id => $mapping) {
           $ids[] = $id;
        }
        return $ids;
    }

    /**
     * return string[]
     */
    public function getIntegrationEntities()
    {
        $entities = [];
        foreach($this->integrationEntityMapping as $entity => $ids) {
            $entities[] = $entity;
        }
        return $entities;
    }

    /**
     * @param string $entity
     *
     * @return int[]
     */
    public function getIntegrationEntityIds($entity)
    {
        $ids = [];
        /** @var array $integrationEntity */
        $integrationEntity = $this->integrationEntityMapping[$entity];
        foreach($integrationEntity as $id => $mapping) {
            $ids[] = $id;
        }
        return $ids;
    }

    /**
     * @param string $integrationEntity
     * @param int    $integrationEntityId
     *
     * @return IntegrationEntityMappingDAO|null
     */
    public function getInternalEntityMapping($integrationEntity, $integrationEntityId)
    {
        if(!isset($this->integrationEntityMapping[$integrationEntity][$integrationEntityId])) {
            return null;
        }
        $entityMappingIndex = $this->integrationEntityMapping[$integrationEntity][$integrationEntityId];
        return $this->entityMappings[$entityMappingIndex];
    }

    /**
     * @param string $internalEntity
     * @param int    $internalEntityId
     *
     * @return IntegrationEntityMappingDAO|null
     */
    public function getIntegrationEntityMapping($internalEntity, $internalEntityId)
    {
        if(!isset($this->internalEntityMapping[$internalEntity][$internalEntityId])) {
            return null;
        }
        $entityMappingIndex = $this->internalEntityMapping[$internalEntity][$internalEntityId];
        return $this->entityMappings[$entityMappingIndex];
    }

    /**
     * @param string $integrationEntity
     * @param string $integrationField
     *
     * @return IntegrationFieldMappingDAO|null
     */
    public function getInternalFieldMapping($integrationEntity, $integrationField)
    {
        if(!isset($this->integrationFieldMapping[$integrationEntity][$integrationField])) {
            return null;
        }
        $fieldMappingIndex = $this->integrationFieldMapping[$integrationEntity][$integrationField];
        return $this->fieldMappings[$fieldMappingIndex];
    }

    /**
     * @param string $internalEntity
     * @param string $internalField
     *
     * @return IntegrationFieldMappingDAO|null
     */
    public function getIntegrationFieldMapping($internalEntity, $internalField)
    {

        if(!isset($this->internalFieldMapping[$internalEntity][$internalField])) {
            return null;
        }
        $fieldMappingIndex = $this->internalEntityMapping[$internalEntity][$internalField];
        return $this->fieldMappings[$fieldMappingIndex];
    }

    /**
     * @param string $internalEntity
     *
     * @return string[]
     */
    public function getFieldMappingsByInternalEntity($internalEntity)
    {
        $fieldMappings = [];
        foreach($this->fieldMappings as $fieldMapping) {
            if($fieldMapping->getInternalEntity() !== $internalEntity) {
                continue;
            }
            $fieldMappings[] = $fieldMapping;
        }
        return $fieldMappings;
    }

    /**
     * @param string $integrationEntity
     *
     * @return string[]
     */
    public function getFieldMappingsByIntegrationEntity($integrationEntity)
    {
        $fieldMappings = [];
        foreach($this->fieldMappings as $fieldMapping) {
            if($fieldMapping->getIntegrationEntity() !== $integrationEntity) {
                continue;
            }
            $fieldMappings[] = $fieldMapping;
        }
        return $fieldMappings;
    }

    /**
     * @return IntegrationEntityMappingDAO[]
     */
    public function getEntityMappings()
    {
        return $this->entityMappings;
    }
}
