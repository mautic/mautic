<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Mapping;

class FieldMappingDAO
{
    /**
     * @var string
     */
    private $internalObject;

    /**
     * @var string
     */
    private $internalField;

    /**
     * @var string
     */
    private $integrationObject;

    /**
     * @var string
     */
    private $integrationField;

    /**
     * @var string
     */
    private $syncDirection;

    /**
     * @var bool
     */
    private $isRequired;

    /**
     * FieldMappingDAO constructor.
     *
     * @param string $internalObject
     * @param string $internalField
     * @param string $integrationObject
     * @param string $integrationField
     * @param string $syncDirection
     * @param bool   $isRequired
     */
    public function __construct($internalObject, $internalField, $integrationObject, $integrationField, $syncDirection, $isRequired)
    {
        $this->internalObject    = $internalObject;
        $this->internalField     = $internalField;
        $this->integrationObject = $integrationObject;
        $this->integrationField  = $integrationField;
        $this->syncDirection     = $syncDirection;
        $this->isRequired        = (bool) $isRequired;
    }

    /**
     * @return string
     */
    public function getInternalObject()
    {
        return $this->internalObject;
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
    public function getIntegrationObject()
    {
        return $this->integrationObject;
    }

    /**
     * @return string
     */
    public function getIntegrationField()
    {
        return $this->integrationField;
    }

    /**
     * @return string
     */
    public function getSyncDirection()
    {
        return $this->syncDirection;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }
}
