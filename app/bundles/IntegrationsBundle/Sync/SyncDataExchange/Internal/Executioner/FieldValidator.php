<?php

declare(strict_types=1);

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
     * @var mixed[]
     */
    private array $fieldSchemaData = [];

    public function __construct(
        private LeadFieldRepository $leadFieldRepository,
        private BulkNotification $bulkNotification
    ) {
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
                } catch (FieldSchemaNotFoundException) {
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

    /**
     * @param mixed[] $schemaDefinition
     */
    private function isFieldLengthValid(array $schemaDefinition, string $normalizedValue): bool
    {
        $schemaLength = SchemaDefinition::getFieldCharLengthLimit($schemaDefinition);

        if (null === $schemaLength) {
            return true;
        }

        $actualLength = mb_strlen($normalizedValue);

        return $actualLength <= $schemaLength;
    }

    /**
     * @param mixed[] $schemaDefinition
     */
    private function isFieldTypeValid(array $schemaDefinition, NormalizedValueDAO $field): bool
    {
        return match ($schemaDefinition['type']) {
            'date', 'datetime', 'time', 'boolean' => $field->getType() === $schemaDefinition['type'],
            'float' => in_array($field->getType(), [
                NormalizedValueDAO::DOUBLE_TYPE,
                NormalizedValueDAO::FLOAT_TYPE,
                NormalizedValueDAO::INT_TYPE,
            ]),
            default => true,
        };
    }

    /**
     * @return mixed[]
     *
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
