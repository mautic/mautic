<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\Notification\BulkNotification;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\Exception\FieldSchemaNotFoundException;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Field\SchemaDefinition;

final class FieldValidator implements FieldValidatorInterface
{
    /**
     * @var LeadFieldRepository
     */
    private $leadFieldRepository;

    /**
     * @var BulkNotification
     */
    private $bulkNotification;

    /**
     * @var array
     */
    private $fieldSchemaData = [];

    public function __construct(LeadFieldRepository $leadFieldRepository, BulkNotification $bulkNotification)
    {
        $this->leadFieldRepository = $leadFieldRepository;
        $this->bulkNotification    = $bulkNotification;
    }

    /**
     * @param ObjectChangeDAO[] $changedObjects
     */
    public function validateFields(string $objectName, array $changedObjects): void
    {
        foreach ($changedObjects as $changedObject) {
            foreach ($changedObject->getFields() as $field) {
                $fieldName = $field->getName();

                try {
                    $schema = $this->getFieldSchema($objectName, $fieldName);
                } catch (FieldSchemaNotFoundException $e) {
                    continue;
                }

                $fieldValue       = $field->getValue();
                $normalizedValue  = $fieldValue->getNormalizedValue();
                $schemaDefinition = SchemaDefinition::getSchemaDefinition(
                    $schema['alias'],
                    $schema['type'],
                    $schema['isUniqueIdentifer'],
                    $schema['charLengthLimit']
                );

                if (is_string($normalizedValue) && !$this->isFieldLengthValid($schemaDefinition, $normalizedValue)) {
                    $changedObject->removeField($fieldName);
                    $message = sprintf("Custom field '%s' with value '%s' exceeded maximum allowed length and was ignored during the sync.", $schema['label'], $normalizedValue);
                    $this->addNotification($message, $changedObject, $fieldName, 'length');
                    continue;
                }

                if (!$this->isFieldTypeValid($schemaDefinition, $fieldValue)) {
                    $changedObject->removeField($fieldName);
                    $message = sprintf("Custom field '%s' of type '%s' did not match integration type '%s' and was ignored during the sync.", $schema['label'], $schema['type'], $fieldValue->getType());
                    $this->addNotification($message, $changedObject, $fieldName, 'type');
                    continue;
                }
            }
        }

        $this->bulkNotification->flush();
    }

    private function isFieldLengthValid(array $schemaDefinition, string $normalizedValue): bool
    {
        $schemaLength = SchemaDefinition::getFieldCharLengthLimit($schemaDefinition);

        if (null === $schemaLength) {
            return true;
        }

        $actualLength = mb_strlen($normalizedValue);

        return $actualLength <= $schemaLength;
    }

    private function isFieldTypeValid(array $schemaDefinition, NormalizedValueDAO $field): bool
    {
        switch ($schemaDefinition['type']) {
            case 'date':
            case 'datetime':
                return in_array($field->getType(), [
                    NormalizedValueDAO::DATE_TYPE,
                    NormalizedValueDAO::DATETIME_TYPE,
                ]);
            case 'time':
                return NormalizedValueDAO::TIME_TYPE === $field->getType();
            case 'boolean':
                return NormalizedValueDAO::BOOLEAN_TYPE === $field->getType();
            case 'float':
                return in_array($field->getType(), [
                    NormalizedValueDAO::DOUBLE_TYPE,
                    NormalizedValueDAO::FLOAT_TYPE,
                    NormalizedValueDAO::INT_TYPE,
                ]);
            default:
                return true;
        }
    }

    /**
     * @throws FieldSchemaNotFoundException
     */
    private function getFieldSchema(string $object, string $alias): array
    {
        if (!isset($this->fieldSchemaData[$object])) {
            $this->fieldSchemaData[$object] = $this->leadFieldRepository->getFieldSchemaData($object);
        }

        if (!isset($this->fieldSchemaData[$object][$alias])) {
            throw new FieldSchemaNotFoundException($object, $alias);
        }

        return $this->fieldSchemaData[$object][$alias];
    }

    private function addNotification(string $message, ObjectChangeDAO $changedObject, string $fieldName, string $type): void
    {
        $integrationName       = $changedObject->getIntegration();
        $integrationObjectName = $changedObject->getObject();
        $integrationObjectId   = $changedObject->getMappedObjectId();
        $deduplicateValue      = $integrationName.'-'.$integrationObjectName.'-'.$fieldName.'-'.$type;

        $this->bulkNotification->addNotification(
            $deduplicateValue,
            sprintf('%s Your %s integration plugin may be configured improperly.', $message, $integrationName),
            $integrationName,
            sprintf('%s %s', $integrationObjectId, $integrationObjectName),
            $integrationObjectName,
            0,
            sprintf('%s %s %s', $integrationName, $integrationObjectName, $integrationObjectId)
        );
    }
}
