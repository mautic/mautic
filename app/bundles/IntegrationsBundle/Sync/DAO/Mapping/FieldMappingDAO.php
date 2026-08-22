<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Mapping;

final class FieldMappingDAO
{
    private readonly bool $isRequired;

    public function __construct(
        private string $internalObject,
        private string $internalField,
        private string $integrationObject,
        private string $integrationField,
        private string $syncDirection,
        bool $isRequired,
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
