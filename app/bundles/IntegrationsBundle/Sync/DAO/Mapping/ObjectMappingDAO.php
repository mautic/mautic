<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Mapping;

class ObjectMappingDAO
{
    const SYNC_TO_MAUTIC       = 'mautic';
    const SYNC_TO_INTEGRATION  = 'integration';
    const SYNC_BIDIRECTIONALLY = 'bidirectional';

    /**
     * @var string
     */
    private $internalObjectName;

    /**
     * @var string
     */
    private $integrationObjectName;

    /**
     * @var array
     */
    private $internalIdMapping = [];

    /**
     * @var array
     */
    private $integrationIdMapping = [];

    /**
     * @var FieldMappingDAO[]
     */
    private $fieldMappings = [];

    public function __construct(string $internalObjectName, string $integrationObjectName)
    {
        $this->internalObjectName    = $internalObjectName;
        $this->integrationObjectName = $integrationObjectName;
    }

    /**
     * @param string $internalField
     * @param string $integrationField
     * @param string $direction
     * @param bool   $isRequired
     *
     * @return ObjectMappingDAO
     */
    public function addFieldMapping($internalField, $integrationField, $direction = self::SYNC_BIDIRECTIONALLY, $isRequired = false): self
    {
        $this->fieldMappings[] = new FieldMappingDAO(
            $this->internalObjectName,
            $internalField,
            $this->integrationObjectName,
            $integrationField,
            $direction,
            $isRequired
        );

        return $this;
    }

    /**
     * @return FieldMappingDAO[]
     */
    public function getFieldMappings(): array
    {
        return $this->fieldMappings;
    }

    public function getMappedIntegrationObjectId(int $internalObjectId): ?int
    {
        if (array_key_exists($internalObjectId, $this->internalIdMapping)) {
            return $this->internalIdMapping[$internalObjectId];
        }

        return null;
    }

    /**
     * @param mixed $integrationObjectId
     *
     * @return mixed|null
     */
    public function getMappedInternalObjectId($integrationObjectId)
    {
        if (array_key_exists($integrationObjectId, $this->integrationIdMapping)) {
            return $this->integrationIdMapping[$integrationObjectId];
        }

        return null;
    }

    public function getInternalObjectName(): string
    {
        return $this->internalObjectName;
    }

    public function getIntegrationObjectName(): string
    {
        return $this->integrationObjectName;
    }
}
