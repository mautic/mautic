<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync;

/**
 * Class IntegrationMappingManualIteratorDAO
 * @package Mautic\PluginBundle\Model\Sync\DAO
 */
class IntegrationMappingManualIteratorDAO
{
    /**
     * @var IntegrationMappingManualDAO
     */
    private $integrationMappingManual;

    /**
     * @var IntegrationEntityMappingDAO[]
     */
    private $entityMappings;

    /**
     * @var IntegrationFieldMappingDAO[]
     */
    private $fieldMappings = [];

    /**
     * IntegrationMappingManualIteratorDAO constructor.
     * @param IntegrationMappingManualDAO $integrationMappingManual
     */
    public function __construct(IntegrationMappingManualDAO $integrationMappingManual)
    {
        $this->integrationMappingManual = clone $integrationMappingManual;
        $this->entityMappings = reset($integrationMappingManual->getEntityMappings());
    }

    /**
     * @return IntegrationEntityMappingDAO|false
     */
    public function getCurrentEntityMapping()
    {
        return current($this->entityMappings);
    }

    /**
     * @return IntegrationEntityMappingDAO|false
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
     * @return IntegrationFieldMappingDAO|false
     */
    public function getCurrentFieldMapping()
    {
        return current($this->fieldMappings);
    }

    /**
     * @return IntegrationFieldMappingDAO|false
     */
    public function getNextFieldMapping()
    {
        return next($this->fieldMappings);
    }
}
