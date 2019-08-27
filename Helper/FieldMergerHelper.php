<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Helper;

use MauticPlugin\IntegrationsBundle\Exception\InvalidFormOptionException;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use MauticPlugin\IntegrationsBundle\Mapping\MappedFieldInfoInterface;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;

class FieldMergerHelper
{
    /**
     * @var ConfigFormSyncInterface
     */
    private $integrationObject;

    /**
     * @var array
     */
    private $currentFieldMappings;

    /**
     * @var MappedFieldInfoInterface[]
     */
    private $allFields;

    public function __construct(ConfigFormSyncInterface $integrationObject, array $currentFieldMappings)
    {
        $this->integrationObject    = $integrationObject;
        $this->currentFieldMappings = $currentFieldMappings;
    }

    /**
     * @throws InvalidFormOptionException
     */
    public function mergeSyncFieldMapping(string $object, array $updatedFieldMappings): void
    {
        $this->allFields = $this->integrationObject->getAllFieldsForMapping($object);

        $this->removeNonExistentFieldMappings($object);

        $this->bindUpdatedFieldMappings($object, $updatedFieldMappings);
    }

    public function getFieldMappings(): array
    {
        return $this->currentFieldMappings;
    }

    private function removeNonExistentFieldMappings(string $object): void
    {
        if (!isset($this->currentFieldMappings[$object])) {
            $this->currentFieldMappings[$object] = [];
        }

        // Remove any fields that no longer exist
        $this->currentFieldMappings[$object] = array_intersect_key($this->currentFieldMappings[$object], $this->allFields);
    }

    /**
     * @throws InvalidFormOptionException
     */
    private function bindUpdatedFieldMappings(string $object, array $updatedFieldMappings): void
    {
        // Merge updated fields into current fields
        foreach ($updatedFieldMappings as $fieldName => $fieldMapping) {
            if (!isset($this->currentFieldMappings[$object][$fieldName])) {
                // Ignore as this field doesn't exist
                continue;
            }

            if (isset($this->currentFieldMappings[$object][$fieldName]) && !$fieldMapping) {
                // Mapping was deleted
                unset($this->currentFieldMappings[$object][$fieldName]);

                continue;
            }

            if (isset($this->currentFieldMappings[$object][$fieldName])) {
                // Merge
                $this->currentFieldMappings[$object][$fieldName] = array_merge($this->currentFieldMappings[$object][$fieldName], $fieldMapping);

                continue;
            }

            if (empty($fieldMapping['mappedField'])) {
                // Ignore this one because just direction was updated without a mapped field

                continue;
            }

            if (!isset($fieldMapping['syncDirection'])) {
                $fieldMapping['syncDirection'] = $this->getFieldDirection($object, $fieldName);
            }

            $this->currentFieldMappings[$object][$fieldName] = $fieldMapping;
        }
    }

    /**
     * @throws InvalidFormOptionException
     */
    private function getFieldDirection(string $object, string $fieldName): string
    {
        $field = $this->allFields[$fieldName];
        $supportedDirections = [];

        if ($field->isBidirectionalSyncEnabled()) {
            $supportedDirections[] = ObjectMappingDAO::SYNC_BIDIRECTIONALLY;
        }

        if ($field->isToIntegrationSyncEnabled()) {
            $supportedDirections[] = ObjectMappingDAO::SYNC_TO_INTEGRATION;
        }

        if ($field->isToMauticSyncEnabled()) {
            $supportedDirections[] = ObjectMappingDAO::SYNC_TO_MAUTIC;
        }

        if (empty($supportedDirections)) {
            throw new InvalidFormOptionException('field "'.$field->getName().'" must allow at least 1 direction for sync');
        }

        if (!empty($this->currentFieldMappings[$object][$fieldName]['syncDirection'])
            && in_array(
                $this->currentFieldMappings[$object][$fieldName]['syncDirection'],
                $supportedDirections
            )) {
            // Keep the already configured value
            return $this->currentFieldMappings[$object][$fieldName]['syncDirection'];
        }

        return reset($supportedDirections);
    }
}
