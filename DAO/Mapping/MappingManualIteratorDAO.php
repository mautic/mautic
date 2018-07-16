<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Mapping;

/**
 * Class MappingManualIteratorDAO
 * @package MauticPlugin\MauticIntegrationsBundle\DAO\Mapping
 */
class MappingManualIteratorDAO
{
    /**
     * @var MappingManualDAO
     */
    private $integrationMappingManual;

    /**
     * @var EntityMappingDAO[]
     */
    private $entityMappings;

    /**
     * @var FieldMappingDAO[]
     */
    private $fieldMappings = [];

    /**
     * MappingManualIteratorDAO constructor.
     * @param MappingManualDAO $integrationMappingManual
     */
    public function __construct(MappingManualDAO $integrationMappingManual)
    {
        $this->integrationMappingManual = clone $integrationMappingManual;
        $this->entityMappings = reset($integrationMappingManual->getEntityMappings());
    }

    /**
     * @return EntityMappingDAO|false
     */
    public function getCurrentEntityMapping()
    {
        return current($this->entityMappings);
    }

    /**
     * @return EntityMappingDAO|false
     */
    public function getNextEntityMapping()
    {
        return next($this->entityMappings);
    }

    /**
     * @param string $entity
     * @param bool $internal
     */
    public function resetFieldMapping($entity, $internal = true)
    {
        if ($internal === true) {
            $this->fieldMappings = reset($this->integrationMappingManual->getFieldMappingsByInternalEntity($entity));
        } else {
            $this->fieldMappings = reset($this->integrationMappingManual->getFieldMappingsByIntegrationEntity($entity));
        }
    }

    /**
     * @return FieldMappingDAO|false
     */
    public function getCurrentFieldMapping()
    {
        return current($this->fieldMappings);
    }

    /**
     * @return FieldMappingDAO|false
     */
    public function getNextFieldMapping()
    {
        return next($this->fieldMappings);
    }
}
