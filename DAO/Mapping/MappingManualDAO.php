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

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Mapping;

/**
 * Class MappingManualDAO
 */
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
     * MappingManualDAO constructor.
     *
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
    public function addObjectMapping(ObjectMappingDAO $objectMappingDAO)
    {
        $internalObjectName    = $objectMappingDAO->getInternalObjectName();
        $integrationObjectName = $objectMappingDAO->getIntegrationObjectName();
        if (!array_key_exists($internalObjectName, $this->objectsMapping)) {
            $this->objectsMapping[$internalObjectName] = [];
        }
        if (!array_key_exists($internalObjectName, $this->internalObjectsMapping)) {
            $this->internalObjectsMapping[$internalObjectName] = [];
        }
        if (!array_key_exists($integrationObjectName, $this->integrationObjectsMapping)) {
            $this->integrationObjectsMapping[$integrationObjectName] = [];
        }
        $this->objectsMapping[$internalObjectName][$integrationObjectName] = $objectMappingDAO;
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
     * @return string[]
     */
    public function getMappedIntegrationObjectsNames(string $internalObjectName): array
    {
        if (!array_key_exists($internalObjectName, $this->internalObjectsMapping)) {
            throw new \LogicException(); // TODO
        }

        return $this->internalObjectsMapping[$internalObjectName];
    }

    /**
     * @param string $integrationObjectName
     *
     * @return string[]
     */
    public function getMappedInternalObjectsNames(string $integrationObjectName): array
    {
        if (!array_key_exists($integrationObjectName, $this->integrationObjectsMapping)) {
            throw new \LogicException(); // TODO
        }

        return $this->integrationObjectsMapping[$integrationObjectName];
    }

    /**
     * @return array
     */
    public function getInternalObjectsNames(): array
    {
        return array_keys($this->internalObjectsMapping);
    }

    /**
     * @param string $internalObjectName
     *
     * @return array
     */
    public function getInternalObjectFieldNames(string $internalObjectName): array
    {
        if (!array_key_exists($internalObjectName, $this->internalObjectsMapping)) {
            throw new \LogicException(); // TODO
        }
        $fields                  = [];
        $integrationObjectsNames = $this->internalObjectsMapping[$internalObjectName];
        foreach ($integrationObjectsNames as $integrationObjectName) {
            /** @var ObjectMappingDAO $objectMappingDAO */
            $objectMappingDAO = $this->objectsMapping[$internalObjectName][$integrationObjectName];
            $fieldMappings    = $objectMappingDAO->getFieldMappings();
            foreach ($fieldMappings as $fieldMapping) {
                if ($fieldMapping->getSyncDirection() === ObjectMappingDAO::SYNC_TO_INTEGRATION) {
                    // Ignore because this field is a one way sync
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
    public function getIntegrationObjectsNames(): array
    {
        return array_keys($this->integrationObjectsMapping);
    }

    /**
     * @param string $integrationObjectName
     *
     * @return array
     */
    public function getIntegrationObjectFieldNames(string $integrationObjectName): array
    {
        if (!array_key_exists($integrationObjectName, $this->integrationObjectsMapping)) {
            throw new \LogicException(); // TODO
        }
        $fields               = [];
        $internalObjectsNames = $this->integrationObjectsMapping[$integrationObjectName];
        foreach ($internalObjectsNames as $internalObjectName) {
            /** @var ObjectMappingDAO $objectMappingDAO */
            $objectMappingDAO = $this->objectsMapping[$internalObjectName][$integrationObjectName];
            $fieldMappings    = $objectMappingDAO->getFieldMappings();
            foreach ($fieldMappings as $fieldMapping) {
                if ($fieldMapping->getSyncDirection() === ObjectMappingDAO::SYNC_TO_MAUTIC) {
                    // Ignore because this field is a one way sync
                    continue;
                }

                $fields[$fieldMapping->getIntegrationField()] = true;
            }
        }

        return array_keys($fields);
    }

    /**
     * @param string $internalObjectName
     * @param string $integrationObjectName
     * @param string $internalFieldName
     *
     * @return string
     */
    public function getIntegrationMappedField(string $internalObjectName, string $integrationObjectName, string $internalFieldName): string
    {
        if (!array_key_exists($internalObjectName, $this->internalObjectsMapping)) {
            throw new \LogicException(); // TODO
        }

        if (!array_key_exists($integrationObjectName, $this->objectsMapping[$internalObjectName])) {
            throw new \LogicException(); // TODO
        }

        /** @var ObjectMappingDAO $objectMappingDAO */
        $objectMappingDAO = $this->objectsMapping[$internalObjectName][$integrationObjectName];
        $fieldMappings    = $objectMappingDAO->getFieldMappings();
        foreach ($fieldMappings as $fieldMapping) {
            if ($fieldMapping->getInternalField() === $internalFieldName) {
                return $fieldMapping->getIntegrationField();
            }
        }

        throw new \LogicException(); // TODO
    }
}
