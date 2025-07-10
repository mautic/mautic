<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Mapping;

use Mautic\IntegrationsBundle\Sync\Exception\FieldNotFoundException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;

class MappingManualDAO
{
    private array $objectsMapping = [];

    private array $internalObjectsMapping = [];

    private array $integrationObjectsMapping = [];

    public function __construct(
        private string $integration,
    ) {
    }

    public function getIntegration(): string
    {
        return $this->integration;
    }

    public function addObjectMapping(ObjectMappingDAO $objectMappingDAO): void
    {
        $internalObjectName    = $objectMappingDAO->getInternalObjectName();
        $integrationObjectName = $objectMappingDAO->getIntegrationObjectName();

        if (!array_key_exists($internalObjectName, $this->objectsMapping)) {
            $this->objectsMapping[$internalObjectName] = [];
        }
        $this->objectsMapping[$internalObjectName][$integrationObjectName] = $objectMappingDAO;

        if (!array_key_exists($internalObjectName, $this->internalObjectsMapping)) {
            $this->internalObjectsMapping[$internalObjectName] = [];
        }
        $this->internalObjectsMapping[$internalObjectName][] = $integrationObjectName;

        if (!array_key_exists($integrationObjectName, $this->integrationObjectsMapping)) {
            $this->integrationObjectsMapping[$integrationObjectName] = [];
        }
        $this->integrationObjectsMapping[$integrationObjectName][] = $internalObjectName;
    }

    public function getObjectMapping(string $internalObjectName, string $integrationObjectName): ?ObjectMappingDAO
    {
        if (!array_key_exists($internalObjectName, $this->objectsMapping)) {
            return null;
        }
        if (!array_key_exists($integrationObjectName, $this->objectsMapping[$internalObjectName])) {
            return null;
        }

        return $this->objectsMapping[$internalObjectName][$integrationObjectName];
    }

    /**
     * @throws ObjectNotFoundException
     */
    public function getMappedIntegrationObjectsNames(string $internalObjectName): array
    {
        if (!array_key_exists($internalObjectName, $this->internalObjectsMapping)) {
            throw new ObjectNotFoundException($internalObjectName);
        }

        return $this->internalObjectsMapping[$internalObjectName];
    }

    /**
     * @throws ObjectNotFoundException
     */
    public function getMappedInternalObjectsNames(string $integrationObjectName): array
    {
        if (!array_key_exists($integrationObjectName, $this->integrationObjectsMapping)) {
            throw new ObjectNotFoundException($integrationObjectName);
        }

        return $this->integrationObjectsMapping[$integrationObjectName];
    }

    public function getInternalObjectNames(): array
    {
        return array_keys($this->internalObjectsMapping);
    }

    /**
     * Get a list of fields that should sync from Mautic to the integration.
     *
     * @throws ObjectNotFoundException
     */
    public function getInternalObjectFieldsToSyncToIntegration(string $internalObjectName): array
    {
        if (!array_key_exists($internalObjectName, $this->internalObjectsMapping)) {
            throw new ObjectNotFoundException($internalObjectName);
        }

        $fields                  = [];
        $integrationObjectsNames = $this->internalObjectsMapping[$internalObjectName];
        foreach ($integrationObjectsNames as $integrationObjectName) {
            /** @var ObjectMappingDAO $objectMappingDAO */
            $objectMappingDAO = $this->objectsMapping[$internalObjectName][$integrationObjectName];
            $fieldMappings    = $objectMappingDAO->getFieldMappings();
            foreach ($fieldMappings as $fieldMapping) {
                if (ObjectMappingDAO::SYNC_TO_MAUTIC === $fieldMapping->getSyncDirection() && !$fieldMapping->isRequired()) {
                    // Ignore because this field is a one way sync from the integration to Mautic nor is required
                    continue;
                }

                $fields[$fieldMapping->getInternalField()] = true;
            }
        }

        return array_keys($fields);
    }

    /**
     * Get a list of internal fields that are required.
     *
     * @throws ObjectNotFoundException
     */
    public function getInternalObjectRequiredFieldNames(string $internalObjectName): array
    {
        if (!array_key_exists($internalObjectName, $this->internalObjectsMapping)) {
            throw new ObjectNotFoundException($internalObjectName);
        }

        $fields                  = [];
        $integrationObjectsNames = $this->internalObjectsMapping[$internalObjectName];
        foreach ($integrationObjectsNames as $integrationObjectName) {
            /** @var ObjectMappingDAO $objectMappingDAO */
            $objectMappingDAO = $this->objectsMapping[$internalObjectName][$integrationObjectName];
            $fieldMappings    = $objectMappingDAO->getFieldMappings();
            foreach ($fieldMappings as $fieldMapping) {
                if (!$fieldMapping->isRequired()) {
                    continue;
                }

                $fields[$fieldMapping->getInternalField()] = true;
            }
        }

        return array_keys($fields);
    }

    public function getIntegrationObjectNames(): array
    {
        return array_keys($this->integrationObjectsMapping);
    }

    /**
     * Get a list of fields that should sync from the integration to Mautic.
     *
     * @throws ObjectNotFoundException
     */
    public function getIntegrationObjectFieldsToSyncToMautic(string $integrationObjectName): array
    {
        if (!array_key_exists($integrationObjectName, $this->integrationObjectsMapping)) {
            throw new ObjectNotFoundException($integrationObjectName);
        }

        $fields               = [];
        $internalObjectsNames = $this->integrationObjectsMapping[$integrationObjectName];

        foreach ($internalObjectsNames as $internalObjectName) {
            /** @var ObjectMappingDAO $objectMappingDAO */
            $objectMappingDAO = $this->objectsMapping[$internalObjectName][$integrationObjectName];
            $fieldMappings    = $objectMappingDAO->getFieldMappings();
            foreach ($fieldMappings as $fieldMapping) {
                if (ObjectMappingDAO::SYNC_TO_INTEGRATION === $fieldMapping->getSyncDirection() && !$fieldMapping->isRequired()) {
                    // Ignore because this field is a one way sync from Mautic to the integration nor a required field
                    continue;
                }

                $fields[$fieldMapping->getIntegrationField()] = true;
            }
        }

        return array_keys($fields);
    }

    /**
     * Get a list of integration fields that are required.
     *
     * @throws ObjectNotFoundException
     */
    public function getIntegrationObjectRequiredFieldNames(string $integrationObjectName): array
    {
        if (!array_key_exists($integrationObjectName, $this->integrationObjectsMapping)) {
            throw new ObjectNotFoundException($integrationObjectName);
        }

        $fields               = [];
        $internalObjectsNames = $this->integrationObjectsMapping[$integrationObjectName];

        foreach ($internalObjectsNames as $internalObjectName) {
            /** @var ObjectMappingDAO $objectMappingDAO */
            $objectMappingDAO = $this->objectsMapping[$internalObjectName][$integrationObjectName];
            $fieldMappings    = $objectMappingDAO->getFieldMappings();
            foreach ($fieldMappings as $fieldMapping) {
                if (!$fieldMapping->isRequired()) {
                    continue;
                }

                $fields[$fieldMapping->getIntegrationField()] = true;
            }
        }

        return array_keys($fields);
    }

    /**
     * @throws FieldNotFoundException
     * @throws ObjectNotFoundException
     */
    public function getIntegrationMappedField(string $integrationObjectName, string $internalObjectName, string $internalFieldName): string
    {
        if (!array_key_exists($internalObjectName, $this->internalObjectsMapping)) {
            throw new ObjectNotFoundException($internalObjectName);
        }

        if (!array_key_exists($integrationObjectName, $this->objectsMapping[$internalObjectName])) {
            throw new ObjectNotFoundException($integrationObjectName);
        }

        /** @var ObjectMappingDAO $objectMappingDAO */
        $objectMappingDAO = $this->objectsMapping[$internalObjectName][$integrationObjectName];
        $fieldMappings    = $objectMappingDAO->getFieldMappings();
        foreach ($fieldMappings as $fieldMapping) {
            if ($fieldMapping->getInternalField() === $internalFieldName) {
                return $fieldMapping->getIntegrationField();
            }
        }

        throw new FieldNotFoundException($internalFieldName, $internalObjectName);
    }

    /**
     * @throws FieldNotFoundException
     * @throws ObjectNotFoundException
     */
    public function getInternalMappedField(string $internalObjectName, string $integrationObjectName, string $integrationFieldName): string
    {
        if (!array_key_exists($internalObjectName, $this->internalObjectsMapping)) {
            throw new ObjectNotFoundException($internalObjectName);
        }

        if (!array_key_exists($integrationObjectName, $this->objectsMapping[$internalObjectName])) {
            throw new ObjectNotFoundException($integrationObjectName);
        }

        /** @var ObjectMappingDAO $objectMappingDAO */
        $objectMappingDAO = $this->objectsMapping[$internalObjectName][$integrationObjectName];
        $fieldMappings    = $objectMappingDAO->getFieldMappings();
        foreach ($fieldMappings as $fieldMapping) {
            if ($fieldMapping->getIntegrationField() === $integrationFieldName) {
                return $fieldMapping->getInternalField();
            }
        }

        throw new FieldNotFoundException($integrationFieldName, $integrationObjectName);
    }
}
