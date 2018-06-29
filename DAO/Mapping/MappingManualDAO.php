<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Mapping;

/**
 * Class MappingManualDAO
 * @package MauticPlugin\MauticIntegrationsBundle\DAO\Mapping
 */
class MappingManualDAO
{
    /**
     * @var EntityMappingDAO[]
     */
    private $entityMappings = [];

    /**
     * @var FieldMappingDAO[]
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
     * @param EntityMappingDAO $integrationEntityMapping
     */
    public function addEntityMapping(EntityMappingDAO $integrationEntityMapping)
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
     * @param FieldMappingDAO $integrationFieldMapping
     */
    public function addFieldMapping(FieldMappingDAO $integrationFieldMapping)
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
     * @param string $entity
     * @return string[]
     */
    public function getInternalFieldsList($entity)
    {
        if(!array_key_exists($entity, $this->internalFieldMapping)) {
            throw new \InvalidArgumentException('Entity "' . $entity . '" wasn\'t found in mapping manual.');
        }
        $list = [];
        foreach($this->internalFieldMapping[$entity] as $field => $fieldMappingIndex) {
            $list[] = $field;
        }
        return $list;
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
     * @return EntityMappingDAO|null
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
     * @return EntityMappingDAO|null
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
     * @return FieldMappingDAO|null
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
     * @return FieldMappingDAO|null
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
     * @return EntityMappingDAO[]
     */
    public function getEntityMappings()
    {
        return $this->entityMappings;
    }
}
