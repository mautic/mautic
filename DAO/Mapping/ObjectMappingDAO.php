<?php
declare(strict_types=1);

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Mapping;

/**
 * Class ObjectMappingDAO
 * @package MauticPlugin\MauticIntegrationsBundle\DAO\Mapping
 */
class ObjectMappingDAO
{
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

    /**
     * ObjectMappingDAO constructor.
     *
     * @param string $internalObjectName
     * @param string $integrationObjectName
     */
    public function __construct(string $internalObjectName, string $integrationObjectName)
    {
        $this->internalObjectName = $internalObjectName;
        $this->integrationObjectName = $integrationObjectName;
    }

    /**
     * @param FieldMappingDAO $fieldMappingDAO
     *
     * @return self
     */
    public function addFieldMapping(FieldMappingDAO $fieldMappingDAO): self
    {
        $this->fieldMappings[] = $fieldMappingDAO;

        return $this;
    }

    /**
     * @return FieldMappingDAO[]
     */
    public function getFieldMappings(): array
    {
        return $this->fieldMappings;
    }

    /**
     * @param int $internalObjectId
     *
     * @return int|null
     */
    public function getMappedIntegrationObjectId(int $internalObjectId): ?int
    {
        if (array_key_exists($internalObjectId, $this->internalIdMapping)) {
            return $this->internalIdMapping[$internalObjectId];
        }
        return null;
    }

    /**
     * @param int $integrationObjectId
     *
     * @return int|null
     */
    public function getMappedInternalObjectId(int $integrationObjectId): ?int
    {
        if (array_key_exists($integrationObjectId, $this->integrationIdMapping)) {
            return $this->integrationIdMapping[$integrationObjectId];
        }
        return null;
    }

    /**
     * @param int $internalObjectId
     * @param int $integrationObjectId
     *
     * @throws \LogicException
     */
    public function mapIds(int $internalObjectId, int $integrationObjectId)
    {
        if(array_key_exists($internalObjectId, $this->internalIdMapping)) {
            throw new \LogicException(); // TODO better exception
        }
        $this->internalIdMapping[$internalObjectId] = $integrationObjectId;
        $this->integrationIdMapping[$integrationObjectId] = $internalObjectId;
    }

    /**
     * @return string
     */
    public function getInternalObjectName(): string
    {
        return $this->internalObjectName;
    }

    /**
     * @return string
     */
    public function getIntegrationObjectName(): string
    {
        return $this->integrationObjectName;
    }
}