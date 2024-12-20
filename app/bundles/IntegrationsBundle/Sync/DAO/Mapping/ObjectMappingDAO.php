<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Mapping;

class ObjectMappingDAO
{
    public const SYNC_TO_MAUTIC       = 'mautic';

    public const SYNC_TO_INTEGRATION  = 'integration';

    public const SYNC_BIDIRECTIONALLY = 'bidirectional';

    private array $internalIdMapping = [];

    private array $integrationIdMapping = [];

    /**
     * @var FieldMappingDAO[]
     */
    private array $fieldMappings = [];

    public function __construct(
        private string $internalObjectName,
        private string $integrationObjectName,
    ) {
    }

    /**
     * @param string $internalField
     * @param string $integrationField
     * @param string $direction
     * @param bool   $isRequired
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
