<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Mapping;

/**
 * Class EntityMappingDAO
 * @package MauticPlugin\MauticIntegrationsBundle\DAO\Mapping
 */
class EntityMappingDAO
{
    /**
     * @var string
     */
    private $internalEntity;

    /**
     * @var int
     */
    private $internalEntityId;

    /**
     * @var string
     */
    private $integrationEntity;

    /**
     * @var int
     */
    private $integrationEntityId;

    /**
     * IntegrationEntityMappingDAO constructor.
     * @param string $internalEntity
     * @param int $internalEntityId
     * @param string $integrationEntity
     * @param int $integrationEntityId
     */
    public function __construct($internalEntity, $internalEntityId, $integrationEntity, $integrationEntityId)
    {
        $this->internalEntity = $internalEntity;
        $this->internalEntityId = $internalEntityId;
        $this->integrationEntity = $integrationEntity;
        $this->integrationEntityId = $integrationEntityId;
    }

    /**
     * @return string
     */
    public function getInternalEntity()
    {
        return $this->internalEntity;
    }

    /**
     * @return int
     */
    public function getInternalEntityId()
    {
        return $this->internalEntityId;
    }

    /**
     * @return string
     */
    public function getIntegrationEntity()
    {
        return $this->integrationEntity;
    }

    /**
     * @return int
     */
    public function getIntegrationEntityId()
    {
        return $this->integrationEntityId;
    }
}
