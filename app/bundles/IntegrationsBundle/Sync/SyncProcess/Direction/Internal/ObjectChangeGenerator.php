<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Internal;

use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Exception\RequiredValueException;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\FieldMappingDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO as ReportFieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ConflictUnresolvedException;
use Mautic\IntegrationsBundle\Sync\Exception\FieldNotFoundException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectSyncSkippedException;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\IntegrationsBundle\Sync\Notification\BulkNotification;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;
use Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Helper\ValueHelper;

class ObjectChangeGenerator
{
    private array $judgementModes = [
        SyncJudgeInterface::HARD_EVIDENCE_MODE,
        SyncJudgeInterface::BEST_EVIDENCE_MODE,
        SyncJudgeInterface::FUZZY_EVIDENCE_MODE,
    ];

    public function __construct(
        private SyncJudgeInterface $syncJudge,
        private ValueHelper $valueHelper,
        private FieldHelper $fieldHelper,
        private BulkNotification $bulkNotification,
    ) {
    }

    /**
     * @return ObjectChangeDAO
     *
     * @throws ObjectNotFoundException
     */
    public function getSyncObjectChange(
        ReportDAO $syncReport,
        MappingManualDAO $mappingManual,
        ObjectMappingDAO $objectMapping,
        ReportObjectDAO $internalObject,
        ReportObjectDAO $integrationObject,
    ) {
        $objectChange = new ObjectChangeDAO(
            $mappingManual->getIntegration(),
            $internalObject->getObject(),
            $internalObject->getObjectId(),
            $integrationObject->getObject(),
            $integrationObject->getObjectId()
        );

        if ($internalObject->getObjectId()) {
            DebugLogger::log(
                $mappingManual->getIntegration(),
                sprintf(
                    "Integration to Mautic; found a match between Mautic's %s:%s object and the integration %s:%s object ",
                    $internalObject->getObject(),
                    (string) $internalObject->getObjectId(),
                    $integrationObject->getObject(),
                    (string) $integrationObject->getObjectId()
                ),
                self::class.':'.__FUNCTION__
            );
        } else {
            DebugLogger::log(
                $mappingManual->getIntegration(),
                sprintf(
                    'Integration to Mautic; no match found for %s:%s',
                    $integrationObject->getObject(),
                    (string) $integrationObject->getObjectId()
                ),
                self::class.':'.__FUNCTION__
            );
        }

        /** @var FieldMappingDAO[] $fieldMappings */
        $fieldMappings = $objectMapping->getFieldMappings();
        foreach ($fieldMappings as $fieldMappingDAO) {
            $this->addFieldToObjectChange($fieldMappingDAO, $syncReport, $mappingManual, $internalObject, $integrationObject, $objectChange);
        }

        // Set the change date/time from the object so that we can update last sync date based on this
        $objectChange->setChangeDateTime($integrationObject->getChangeDateTime());

        return $objectChange;
    }

    /**
     * @throws ObjectNotFoundException
     * @throws ObjectSyncSkippedException
     */
    private function addFieldToObjectChange(
        FieldMappingDAO $fieldMappingDAO,
        ReportDAO $syncReport,
        MappingManualDAO $mappingManual,
        ReportObjectDAO $internalObject,
        ReportObjectDAO $integrationObject,
        ObjectChangeDAO $objectChange,
    ): void {
        // Skip adding fields for the pull process that should sync to integration only.
        if (ObjectMappingDAO::SYNC_TO_INTEGRATION === $fieldMappingDAO->getSyncDirection()) {
            DebugLogger::log(
                $mappingManual->getIntegration(),
                sprintf(
                    "Integration to Mautic; the %s object's field %s was skipped because it's configured to sync to the integration",
                    $internalObject->getObject(),
                    $fieldMappingDAO->getInternalField()
                ),
                __CLASS__.':'.__FUNCTION__
            );

            return;
        }

        try {
            $integrationFieldState = $integrationObject->getField($fieldMappingDAO->getIntegrationField())->getState();
            $internalFieldState    = $this->getFieldState(
                $fieldMappingDAO->getInternalObject(),
                $fieldMappingDAO->getInternalField(),
                $integrationFieldState
            );

            $integrationInformationChangeRequest = $syncReport->getInformationChangeRequest(
                $integrationObject->getObject(),
                $integrationObject->getObjectId(),
                $fieldMappingDAO->getIntegrationField()
            );
        } catch (FieldNotFoundException) {
            return;
        }

        try {
            // If syncing bidirectional, let the sync judge determine what value should be used for the field
            if (ObjectMappingDAO::SYNC_BIDIRECTIONALLY === $fieldMappingDAO->getSyncDirection()) {
                $this->judgeThenAddFieldToObjectChange($mappingManual, $internalObject, $fieldMappingDAO, $integrationInformationChangeRequest, $objectChange, $internalFieldState);

                return;
            }

            $newValue = $this->valueHelper->getValueForMautic(
                $integrationInformationChangeRequest->getNewValue(),
                $internalFieldState,
                $fieldMappingDAO->getSyncDirection()
            );
        } catch (RequiredValueException $e) {
            $isNewObject = (null === $internalObject->getObjectId());

            $this->notifyAboutInvalidValue($e, $fieldMappingDAO, $integrationInformationChangeRequest, $isNewObject);

            if ($isNewObject) {
                // Empty required field for new contact means completely rejected contact from sync
                throw new ObjectSyncSkippedException(sprintf("Skipping creating lead '%s' because required value for internal field '%s' is empty", $integrationInformationChangeRequest->getObjectId(), $fieldMappingDAO->getInternalField()));
            }

            return; // Empty required field for existing contact is skipped
        } catch (InvalidValueException) {
            return; // Field has to be skipped
        }

        // Add the value to the field based on the field state
        $objectChange->addField(
            new FieldDAO($fieldMappingDAO->getInternalField(), $newValue),
            $internalFieldState
        );

        // ObjectMappingDAO::SYNC_TO_MAUTIC
        DebugLogger::log(
            $mappingManual->getIntegration(),
            sprintf(
                'Integration to Mautic; syncing %s %s with a value of %s',
                $internalFieldState,
                $fieldMappingDAO->getInternalField(),
                var_export($newValue->getNormalizedValue(), true)
            ),
            self::class.':'.__FUNCTION__
        );
    }

    private function judgeThenAddFieldToObjectChange(
        MappingManualDAO $mappingManual,
        ReportObjectDAO $internalObject,
        FieldMappingDAO $fieldMappingDAO,
        InformationChangeRequestDAO $integrationInformationChangeRequest,
        ObjectChangeDAO $objectChange,
        string $fieldState,
    ): void {
        try {
            $internalField = $internalObject->getField($fieldMappingDAO->getInternalField());
        } catch (FieldNotFoundException) {
            $internalField = null;
        }

        if (!$internalField) {
            $newValue = $this->valueHelper->getValueForMautic(
                $integrationInformationChangeRequest->getNewValue(),
                $fieldState,
                $fieldMappingDAO->getSyncDirection()
            );

            $objectChange->addField(
                new FieldDAO($fieldMappingDAO->getInternalField(), $newValue),
                $fieldState
            );

            DebugLogger::log(
                $mappingManual->getIntegration(),
                sprintf(
                    "Integration to Mautic; the sync is bidirectional but no conflicts were found so syncing the %s object's %s field %s with a value of %s",
                    $internalObject->getObject(),
                    $fieldState,
                    $fieldMappingDAO->getInternalField(),
                    var_export($newValue->getNormalizedValue(), true)
                ),
                self::class.':'.__FUNCTION__
            );

            return;
        }

        $internalInformationChangeRequest = new InformationChangeRequestDAO(
            MauticSyncDataExchange::NAME,
            $internalObject->getObject(),
            $internalObject->getObjectId(),
            $internalField->getName(),
            $internalField->getValue()
        );

        $possibleChangeDateTime = $internalObject->getChangeDateTime();
        $certainChangeDateTime  = $internalField->getChangeDateTime();

        // If we know certain change datetime and it's newer than possible change datetime
        // then we have to update possible change datetime otherwise comparision doesn't work correctly
        if ($certainChangeDateTime && ($certainChangeDateTime > $possibleChangeDateTime)) {
            $possibleChangeDateTime = $certainChangeDateTime;
        }

        $internalInformationChangeRequest->setPossibleChangeDateTime($possibleChangeDateTime);
        $internalInformationChangeRequest->setCertainChangeDateTime($certainChangeDateTime);

        // There is a conflict so let the judge determine which value comes out on top
        foreach ($this->judgementModes as $judgeMode) {
            try {
                $this->makeJudgement(
                    $mappingManual,
                    $judgeMode,
                    $fieldMappingDAO,
                    $objectChange,
                    $integrationInformationChangeRequest,
                    $internalInformationChangeRequest,
                    $fieldState
                );

                break;
            } catch (ConflictUnresolvedException) {
                DebugLogger::log(
                    $mappingManual->getIntegration(),
                    sprintf(
                        'Integration to Mautic; no winner was determined using the %s judging mode for object %s field %s',
                        $judgeMode,
                        $internalObject->getObject(),
                        $fieldMappingDAO->getInternalField()
                    ),
                    self::class.':'.__FUNCTION__
                );
            }
        }
    }

    /**
     * @throws ConflictUnresolvedException
     */
    private function makeJudgement(
        MappingManualDAO $mappingManual,
        string $judgeMode,
        FieldMappingDAO $fieldMappingDAO,
        ObjectChangeDAO $objectChange,
        InformationChangeRequestDAO $integrationInformationChangeRequest,
        InformationChangeRequestDAO $internalInformationChangeRequest,
        string $fieldState,
    ): void {
        $winningChangeRequest = $this->syncJudge->adjudicate(
            $judgeMode,
            $internalInformationChangeRequest,
            $integrationInformationChangeRequest
        );

        $newValue = $this->valueHelper->getValueForMautic(
            $winningChangeRequest->getNewValue(),
            $fieldState,
            $fieldMappingDAO->getSyncDirection()
        );

        $objectChange->addField(
            new FieldDAO($fieldMappingDAO->getInternalField(), $newValue),
            $fieldState
        );

        DebugLogger::log(
            $mappingManual->getIntegration(),
            sprintf(
                "Integration to Mautic; sync judge determined to sync %s to the %s object's %s field %s with a value of %s using the %s judging mode",
                $winningChangeRequest->getIntegration(),
                $winningChangeRequest->getObject(),
                $fieldState,
                $fieldMappingDAO->getInternalField(),
                var_export($newValue->getNormalizedValue(), true),
                $judgeMode
            ),
            self::class.':'.__FUNCTION__
        );
    }

    private function getFieldState(string $object, string $field, string $integrationFieldState): string
    {
        // If this is a Mautic required field, return required
        if (isset($this->fieldHelper->getRequiredFields($object)[$field])) {
            return ReportFieldDAO::FIELD_REQUIRED;
        }

        return $integrationFieldState;
    }

    private function notifyAboutInvalidValue(
        InvalidValueException $e,
        FieldMappingDAO $fieldMappingDAO,
        InformationChangeRequestDAO $integrationInformationChangeRequest,
        bool $isNewObject,
    ): void {
        $newObjectSkippedMessagePart = ($isNewObject) ? ' New object sync skipped.' : '';

        $message = sprintf(
            "Field '%s' for object ID '%s' mapped to internal '%s' with value '%s'",
            $integrationInformationChangeRequest->getField(),
            $integrationInformationChangeRequest->getObjectId(),
            $fieldMappingDAO->getIntegrationField(),
            $integrationInformationChangeRequest->getNewValue()->getOriginalValue()
        );

        $deduplicateValue = static::class.'-'.
            $integrationInformationChangeRequest->getIntegration().'-'.
            $fieldMappingDAO->getInternalObject().'-'.
            $integrationInformationChangeRequest->getField();

        $this->bulkNotification->addNotification(
            $deduplicateValue,
            $e->getMessage().$newObjectSkippedMessagePart,
            $integrationInformationChangeRequest->getIntegration(),
            $fieldMappingDAO->getIntegrationObject(),
            $fieldMappingDAO->getInternalObject(),
            0,
            $message
        );

        $this->bulkNotification->flush();
    }
}
