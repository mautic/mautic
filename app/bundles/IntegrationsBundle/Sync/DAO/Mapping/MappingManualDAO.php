<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

declare(strict_types=1);

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping;

use MauticPlugin\IntegrationsBundle\Sync\Exception\FieldNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;

class MappingManualDAO
{
    private $integration;

    /**
     * @var array
     */
    private $objectsMapping = [];

    /**
     * @var array
     */
    private $internalObjectsMapping = [];

    /**
     * @var array
     */
    private $integrationObjectsMapping = [];

    /**
     * @param string $integration
     */
    public function __construct(string $integration)
    {
        $this->integration = $integration;
    }

    /**
     * @return string
     */
    public function getIntegration(): string
    {
        return $this->integration;
    }

    /**
     * @param ObjectMappingDAO $objectMappingDAO
     */
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

    /**
     * @param string $internalObjectName
     * @param string $integrationObjectName
     *
     * @return ObjectMappingDAO|null
     */
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
     * @param string $internalObjectName
     *
     * @return array
     *
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
     * @param string $integrationObjectName
     *
     * @return array
     *
     * @throws ObjectNotFoundException
     */
    public function getMappedInternalObjectsNames(string $integrationObjectName): array
    {
        if (!array_key_exists($integrationObjectName, $this->integrationObjectsMapping)) {
            throw new ObjectNotFoundException($integrationObjectName);
        }

        return $this->integrationObjectsMapping[$integrationObjectName];
    }

    /**
     * @return array
     */
    public function getInternalObjectNames(): array
    {
        return array_keys($this->internalObjectsMapping);
    }

    /**
     * Get a list of fields that should sync from Mautic to the integration.
     *
     * @param string $internalObjectName
     *
     * @return array
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
     * @param string $internalObjectName
     *
     * @return array
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

    /**
     * @return array
     */
    public function getIntegrationObjectNames(): array
    {
        return array_keys($this->integrationObjectsMapping);
    }

    /**
     * Get a list of fields that should sync from the integration to Mautic.
     *
     * @param string $integrationObjectName
     *
     * @return array
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
     * @param string $integrationObjectName
     *
     * @return array
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
     * @param string $integrationObjectName
     * @param string $internalObjectName
     * @param string $internalFieldName
     *
     * @return string
     *
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
     * @param string $internalObjectName
     * @param string $integrationObjectName
     * @param string $integrationFieldName
     *
     * @return string
     *
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
