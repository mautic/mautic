<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Mapping;

/**
 * Class FieldMappingDAO
 * @package MauticPlugin\MauticIntegrationsBundle\DAO\Mapping
 */
class FieldMappingDAO
{
    /**
     * @var string
     */
    private $internalEntity;

    /**
     * @var string
     */
    private $internalField;

    /**
     * @var string
     */
    private $integrationEntity;

    /**
     * @var string
     */
    private $integrationField;

    /**
     * IntegrationFieldMappingDAO constructor.
     * @param string $internalEntity
     * @param string $internalField
     * @param string $integrationEntity
     * @param string $integrationField
     */
    public function __construct($internalEntity, $internalField, $integrationEntity, $integrationField)
    {
        $this->internalEntity = $internalEntity;
        $this->internalField = $internalField;
        $this->integrationEntity = $integrationEntity;
        $this->integrationField = $integrationField;
    }

    /**
     * @return string
     */
    public function getInternalEntity()
    {
        return $this->internalEntity;
    }

    /**
     * @return string
     */
    public function getInternalField()
    {
        return $this->internalField;
    }

    /**
     * @return string
     */
    public function getIntegrationEntity()
    {
        return $this->integrationEntity;
    }

    /**
     * @return string
     */
    public function getIntegrationField()
    {
        return $this->integrationField;
    }
}
