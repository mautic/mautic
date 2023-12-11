<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Mapping;

class FieldMappingDAO
{
    private bool $isRequired;

    /**
     * @param string $internalObject
     * @param string $internalField
     * @param string $integrationObject
     * @param string $integrationField
     * @param string $syncDirection
     * @param bool   $isRequired
     */
    public function __construct(
        private $internalObject,
        private $internalField,
        private $integrationObject,
        private $integrationField,
        private $syncDirection,
        $isRequired
    ) {
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
