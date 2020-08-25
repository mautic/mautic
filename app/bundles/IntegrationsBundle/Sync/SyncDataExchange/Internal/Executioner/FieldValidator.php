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
use Mautic\IntegrationsBundle\Sync\Notification\Helper\UserNotificationHelper;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Field\SchemaDefinition;

class FieldValidator
{
    /**
     * @var LeadFieldRepository
     */
    private $leadFieldRepository;

    /**
     * @var UserNotificationHelper
     */
    private $notificationHelper;

    /**
     * @var array
     */
    private $fieldSchemaData = [];

    public function __construct(LeadFieldRepository $leadFieldRepository, UserNotificationHelper $notificationHelper)
    {
        $this->leadFieldRepository = $leadFieldRepository;
        $this->notificationHelper  = $notificationHelper;
    }

    /**
     * @param ObjectChangeDAO[] $changedObjects
     */
    public function validateFields(string $objectName, array $changedObjects): void
    {
        foreach ($changedObjects as $changedObject) {
            foreach ($changedObject->getFields() as $field) {
                $fieldName = $field->getName();
                $schema    = $this->getFieldSchema($objectName, $fieldName);

                if (null === $schema) {
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
                    $message = sprintf("Field '%s' with value '%s' exceeded maximum allowed length and was ignored during the sync", $schema['label'], $normalizedValue);
                    $this->writeNotification($message, $changedObject);
                    continue;
                }

                if (!$this->isFieldTypeValid($schemaDefinition, $fieldValue)) {
                    $changedObject->removeField($fieldName);
                    $message = sprintf("Field '%s' of type '%s' did not match type '%s' and was ignored during the sync", $schema['label'], $schema['type'], $fieldValue->getType());
                    $this->writeNotification($message, $changedObject);
                    continue;
                }
            }
        }
    }

    private function isFieldLengthValid(array $schemaDefinition, $normalizedValue): bool
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
                return NormalizedValueDAO::TIME_TYPE == $field->getType();
            case 'boolean':
                return NormalizedValueDAO::BOOLEAN_TYPE == $field->getType();
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

    private function getFieldSchema(string $object, string $alias): ?array
    {
        if (!isset($this->fieldSchemaData[$object])) {
            $this->fieldSchemaData[$object] = $this->leadFieldRepository->getFieldSchemaData($object);
        }

        return $this->fieldSchemaData[$object][$alias] ?? null;
    }

    private function writeNotification(string $message, ObjectChangeDAO $changedObject): void
    {
        $this->notificationHelper->writeNotification(
            $message,
            $changedObject->getIntegration(),
            $changedObject->getMappedObjectId(),
            $changedObject->getObject(),
            0,
            sprintf('SF object %s', $changedObject->getMappedObjectId())
        );
    }
}
