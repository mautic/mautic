<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncProcess;

use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectDeletedException;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\FieldMappingDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use MauticPlugin\IntegrationsBundle\Sync\Mapping\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\SyncDate\SyncDateHelper;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ConflictUnresolvedException;
use MauticPlugin\IntegrationsBundle\Sync\Exception\FieldNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;

/**
 * Class SyncProcess
 */
class SyncProcess
{
    private $syncJudge;

    /**
     * @var MappingManualDAO
     */
    private $mappingManualDAO;

    /**
     * @var MauticSyncDataExchange
     */
    private $internalSyncDataExchange;

    /**
     * @var SyncDataExchangeInterface
     */
    private $integrationSyncDataExchange;

    /**
     * @var SyncDateHelper
     */
    private $syncDateHelper;

    /**
     * @var MappingHelper
     */
    private $mappingHelper;

    /**
     * @var bool
     */
    private $isFirstTimeSync = false;

    /**
     * @var \DateTimeInterface|null
     */
    private $syncFromDateTime;

    /**
     * @var \DateTimeInterface|null
     */
    private $syncToDateTime;

    /**
     * @var int
     */
    private $syncIteration;

    /**
     * SyncProcess constructor.
     *
     * @param SyncJudgeInterface        $syncJudge
     * @param MappingManualDAO          $mappingManualDAO
     * @param SyncDataExchangeInterface $internalSyncDataExchange
     * @param SyncDataExchangeInterface $integrationSyncDataExchange
     * @param SyncDateHelper            $syncDateHelper
     * @param MappingHelper             $mappingHelper
     * @param                           $isFirstTimeSync
     * @param \DateTimeInterface|null   $syncFromDateTime
     * @param \DateTimeInterface|null   $syncToDateTime
     */
    public function __construct(
        SyncJudgeInterface $syncJudge,
        MappingManualDAO $mappingManualDAO,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncDataExchangeInterface $integrationSyncDataExchange,
        SyncDateHelper $syncDateHelper,
        MappingHelper $mappingHelper,
        $isFirstTimeSync,
        \DateTimeInterface $syncFromDateTime = null,
        \DateTimeInterface $syncToDateTime = null
    )
    {
        $this->syncJudge                   = $syncJudge;
        $this->mappingManualDAO            = $mappingManualDAO;
        $this->internalSyncDataExchange    = $internalSyncDataExchange;
        $this->integrationSyncDataExchange = $integrationSyncDataExchange;
        $this->mappingManualDAO            = $mappingManualDAO;
        $this->syncDateHelper              = $syncDateHelper;
        $this->mappingHelper               = $mappingHelper;
        $this->isFirstTimeSync             = $isFirstTimeSync;
        $this->syncFromDateTime            = $syncFromDateTime;
        $this->syncToDateTime              = $syncToDateTime;
    }

    /**
     * Execute sync with integration
     */
    public function execute()
    {
        defined('MAUTIC_INTEGRATION_ACTIVE_SYNC') or define('MAUTIC_INTEGRATION_ACTIVE_SYNC', 1);

        $this->syncDateHelper->setSyncDateTimes($this->syncFromDateTime, $this->syncToDateTime);

        $this->executeIntegrationSync();
        $this->executeInternalSync();
    }

    private function executeIntegrationSync()
    {
        $this->syncIteration = 1;
        do {
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf("Integration to Mautic; syncing iteration %s", $this->syncIteration),
                __CLASS__.':'.__FUNCTION__
            );

            $syncReport = $this->generateIntegrationSyncReport();
            if (!$syncReport->shouldSync()) {
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    "Integration to Mautic; no objects were mapped to be synced",
                    __CLASS__.':'.__FUNCTION__
                );
                break;
            }

            // Update the mappings in case objects have been converted such as Lead -> Contact
            $this->mappingHelper->remapIntegrationObjects($syncReport->getRemappedObjects());

            // Convert the integrations' report into an "order" or instructions for Mautic
            $syncOrder = $this->generateInternalSyncOrder($syncReport);
            if (!$syncOrder->shouldSync()) {
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    "Integration to Mautic; no object changes were recorded possible due to field direction configurations",
                    __CLASS__.':'.__FUNCTION__
                );

                break;
            }

            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Integration to Mautic; syncing %d total objects",
                    $syncOrder->getObjectCount()
                ),
                __CLASS__.':'.__FUNCTION__
            );

            // Execute the sync instructions
            $this->internalSyncDataExchange->executeSyncOrder($syncOrder);
            // Fetch the next iteration/batch
            ++$this->syncIteration;
        } while (true);
    }

    private function executeInternalSync()
    {
        $this->syncIteration = 1;
        do {
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf("Mautic to integration; syncing iteration %s", $this->syncIteration),
                __CLASS__.':'.__FUNCTION__
            );

            $syncReport = $this->generateInternalSyncReport();
            if (!$syncReport->shouldSync()) {
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    "Mautic to integration; no objects were mapped to be synced",
                    __CLASS__.':'.__FUNCTION__
                );
                break;
            }

            // Convert the internal report into an "order" or instructions for the integration
            $syncOrder = $this->generateIntegrationSyncOrder($syncReport);
            if (!$syncOrder->shouldSync()) {
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    "Mautic to integration; no object changes were recorded possible due to field direction configurations",
                    __CLASS__.':'.__FUNCTION__
                );

                break;
            }

            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Mautic to integration; syncing %d total objects",
                    $syncOrder->getObjectCount()
                ),
                __CLASS__.':'.__FUNCTION__
            );

            // Execute the sync instructions
            $this->integrationSyncDataExchange->executeSyncOrder($syncOrder);

            // Save the mappings between Mautic objects and the integration's objects
            $this->mappingHelper->saveObjectMappings($syncOrder->getObjectMappings());

            // Update the mappings between Mautic objects and the integration's objects if applicable
            $this->mappingHelper->updateObjectMappings($syncOrder->getUpdatedObjectMappings());

            // Tell sync that these objects have been deleted and not to continue re-syncing them
            $this->mappingHelper->markAsDeleted($syncOrder->getDeletedObjects());

            // Cleanup field tracking for successfully synced objects
            $this->internalSyncDataExchange->cleanupProcessedObjects($syncOrder->getSuccessfullySyncedObjects());

            // Fetch the next iteration/batch
            ++$this->syncIteration;
        } while (true);
    }

    /**
     * @return ReportDAO
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException
     */
    private function generateIntegrationSyncReport()
    {
        $integrationRequestDAO = new RequestDAO($this->syncIteration, $this->isFirstTimeSync);

        $integrationObjectsNames = $this->mappingManualDAO->getIntegrationObjectsNames();
        foreach ($integrationObjectsNames as $integrationObjectName) {
            $integrationObjectFields = $this->mappingManualDAO->getIntegrationObjectFieldNames($integrationObjectName);

            if (count($integrationObjectFields) === 0) {
                // No fields configured for a sync
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    sprintf(
                        "Integration to Mautic; there are no fields for the %s object",
                        $integrationObjectName
                    ),
                    __CLASS__.':'.__FUNCTION__
                );

                continue;
            }

            $objectSyncFromDateTime = $this->syncDateHelper->getSyncFromDateTime($this->mappingManualDAO->getIntegration(), $integrationObjectName);
            $objectSyncToDateTime   = $this->syncDateHelper->getSyncToDateTime();
            $lastObjectSyncDateTime = $this->syncDateHelper->getLastSyncDateForObject($this->mappingManualDAO->getIntegration(), $integrationObjectName);
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Integration to Mautic; syncing from %s to %s for the %s object with %d fields but giving the option to sync from the object's last sync date of %s",
                    $objectSyncFromDateTime->format('Y-m-d H:i:s'),
                    $objectSyncToDateTime->format('Y-m-d H:i:s'),
                    ($lastObjectSyncDateTime) ? $lastObjectSyncDateTime->format('Y-m-d H:i:s') : 'null',
                    $integrationObjectName,
                    count($integrationObjectFields)
                ),
                __CLASS__.':'.__FUNCTION__
            );

            $integrationRequestObject = new RequestObjectDAO(
                $integrationObjectName,
                $objectSyncFromDateTime,
                $objectSyncToDateTime,
                $lastObjectSyncDateTime
            );
            foreach ($integrationObjectFields as $integrationObjectField) {
                $integrationRequestObject->addField($integrationObjectField);
            }
            $integrationRequestDAO->addObject($integrationRequestObject);
        }

        $integrationSyncReport = $integrationRequestDAO->shouldSync()
            ? $this->integrationSyncDataExchange->getSyncReport($integrationRequestDAO)
            :
            new ReportDAO($this->mappingManualDAO->getIntegration());

        return $integrationSyncReport;
    }

    /**
     * @return ReportDAO
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException
     */
    private function generateInternalSyncReport()
    {
        $internalRequestDAO = new RequestDAO($this->syncIteration, $this->isFirstTimeSync);

        $internalObjectsNames = $this->mappingManualDAO->getInternalObjectsNames();
        foreach ($internalObjectsNames as $internalObjectName) {
            $internalObjectFields = $this->mappingManualDAO->getInternalObjectFieldNames($internalObjectName);
            if (count($internalObjectFields) === 0) {
                // No fields configured for a sync
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    sprintf(
                        "Mautic to integration; there are no fields for the %s object",
                        $internalObjectName
                    ),
                    __CLASS__.':'.__FUNCTION__
                );

                continue;
            }

            $objectSyncFromDateTime = $this->syncDateHelper->getSyncFromDateTime(MauticSyncDataExchange::NAME, $internalObjectName);
            $objectSyncToDateTime   = $this->syncDateHelper->getSyncToDateTime();
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Mautic to integration; syncing from %s to %s for the %s object with %d fields",
                    $objectSyncFromDateTime->format('Y-m-d H:i:s'),
                    $objectSyncToDateTime->format('Y-m-d H:i:s'),
                    $internalObjectName,
                    count($internalObjectFields)
                ),
                __CLASS__.':'.__FUNCTION__
            );

            $internalRequestObject  = new RequestObjectDAO($internalObjectName, $objectSyncFromDateTime, $objectSyncToDateTime);
            foreach ($internalObjectFields as $internalObjectField) {
                $internalRequestObject->addField($internalObjectField);
            }
            $internalRequestDAO->addObject($internalRequestObject);
        }

        $internalSyncReport = $internalRequestDAO->shouldSync()
            ? $this->internalSyncDataExchange->getSyncReport($internalRequestDAO)
            :
            new ReportDAO(MauticSyncDataExchange::NAME);

        return $internalSyncReport;
    }

    /**
     * Generates an "order" which translates the report from the integration into instructions for Mautic to sync
     *
     * @param ReportDAO $syncReport
     *
     * @return OrderDAO
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException
     */
    private function generateInternalSyncOrder(ReportDAO $syncReport)
    {
        $syncOrder = new OrderDAO($this->syncDateHelper->getSyncDateTime(), $this->isFirstTimeSync);

        $integrationObjectsNames = $this->mappingManualDAO->getIntegrationObjectsNames();
        foreach ($integrationObjectsNames as $integrationObjectName) {
            $integrationObjects         = $syncReport->getObjects($integrationObjectName);
            $mappedInternalObjectsNames = $this->mappingManualDAO->getMappedInternalObjectsNames($integrationObjectName);

            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Integration to Mautic; found %d objects for the %s object mapped to the %s Mautic object(s)",
                    count($integrationObjects),
                    $integrationObjectName,
                    implode(", ", $mappedInternalObjectsNames)
                ),
                __CLASS__.':'.__FUNCTION__
            );

            foreach ($mappedInternalObjectsNames as $mappedInternalObjectName) {
                $objectMapping = $this->mappingManualDAO->getObjectMapping($mappedInternalObjectName, $integrationObjectName);
                foreach ($integrationObjects as $integrationObject) {
                    $internalObject = $this->internalSyncDataExchange->getConflictedInternalObject($this->mappingManualDAO, $mappedInternalObjectName, $integrationObject);
                    $objectChange   = $this->getSyncObjectChangeIntegrationToMautic($syncReport, $objectMapping, $integrationObject, $internalObject);

                    if ($objectChange->shouldSync()) {
                        $syncOrder->addObjectChange($objectChange);
                    }
                }
            }
        }

        return $syncOrder;
    }

    /**
     * Generates an "order" which translates the report from Mautic into instructions for the Integration to sync
     *
     * @param ReportDAO $syncReport
     *
     * @return OrderDAO
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException
     */
    private function generateIntegrationSyncOrder(ReportDAO $syncReport)
    {
        $syncOrder = new OrderDAO($this->syncDateHelper->getSyncDateTime(), $this->isFirstTimeSync);

        $internalObjectNames = $this->mappingManualDAO->getInternalObjectsNames();
        foreach ($internalObjectNames as $internalObjectName) {
            $internalObjects              = $syncReport->getObjects($internalObjectName);
            $mappedIntegrationObjectNames = $this->mappingManualDAO->getMappedIntegrationObjectsNames($internalObjectName);

            foreach ($mappedIntegrationObjectNames as $mappedIntegrationObjectName) {
                $objectMapping = $this->mappingManualDAO->getObjectMapping($internalObjectName, $mappedIntegrationObjectName);
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    sprintf(
                        "Mautic to integration; syncing %d objects for the %s object mapped to the %s integration object",
                        count($internalObjects),
                        $internalObjectName,
                        $mappedIntegrationObjectName
                    ),
                    __CLASS__.':'.__FUNCTION__
                );

                foreach ($internalObjects as $internalObject) {
                    try {
                        $integrationObject = $this->internalSyncDataExchange->getMappedIntegrationObject(
                            $this->mappingManualDAO->getIntegration(),
                            $mappedIntegrationObjectName,
                            $internalObject
                        );

                        $objectChange = $this->getSyncObjectChangeMauticToIntegration(
                            $syncReport,
                            $objectMapping,
                            $internalObject,
                            $integrationObject
                        );

                        if ($objectChange->shouldSync()) {
                            $syncOrder->addObjectChange($objectChange);
                        }
                    } catch (ObjectDeletedException $exception) {
                        DebugLogger::log(
                            $this->mappingManualDAO->getIntegration(),
                            sprintf(
                                "Mautic to integration; Mautic's %s:%s object was deleted from the integration so don't try to sync",
                                $internalObject->getObject(),
                                $internalObject->getObjectId()
                            ),
                            __CLASS__.':'.__FUNCTION__
                        );

                        continue;
                    }
                }
            }
        }

        return $syncOrder;
    }

    /**
     * Generates a ObjectChangeDAO from Integration to Mautic
     *
     * @param ReportDAO $syncReport
     * @param ObjectMappingDAO $objectMapping
     * @param ReportObjectDAO  $integrationObject
     * @param ReportObjectDAO  $internalObject
     *
     * @return ObjectChangeDAO
     * @throws ObjectNotFoundException
     */
    private function getSyncObjectChangeIntegrationToMautic(
        ReportDAO $syncReport,
        ObjectMappingDAO $objectMapping,
        ReportObjectDAO $integrationObject,
        ReportObjectDAO $internalObject
    ) {
        $objectChange = new ObjectChangeDAO(
            $syncReport->getIntegration(),
            $internalObject->getObject(),
            $internalObject->getObjectId(),
            $integrationObject->getObject(),
            $integrationObject->getObjectId()
        );

        if ($internalObject->getObjectId()) {
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Integration to Mautic; found a match between Mautic's %s:%s object and the integration %s:%s object ",
                    $internalObject->getObject(),
                    (string) $internalObject->getObjectId(),
                    $integrationObject->getObject(),
                    (string) $integrationObject->getObjectId()
                ),
                __CLASS__.':'.__FUNCTION__
            );
        } else {
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Integration to Mautic; no match found for %s:%s",
                    $integrationObject->getObject(),
                    (string) $integrationObject->getObjectId()
                ),
                __CLASS__.':'.__FUNCTION__
            );
        }

        /** @var FieldMappingDAO[] $fieldMappings */
        $fieldMappings = $objectMapping->getFieldMappings();
        foreach ($fieldMappings as $fieldMappingDAO) {
            try {
                $integrationInformationChangeRequest = $syncReport->getInformationChangeRequest(
                    $integrationObject->getObject(),
                    $integrationObject->getObjectId(),
                    $fieldMappingDAO->getIntegrationField()
                );
            } catch (FieldNotFoundException $e) {
                continue;
            }

            // Perform the sync in the direction instructed
            switch ($fieldMappingDAO->getSyncDirection()) {
                case ObjectMappingDAO::SYNC_TO_MAUTIC:
                    $objectChange->addField(
                        new FieldDAO($fieldMappingDAO->getInternalField(), $integrationInformationChangeRequest->getNewValue())
                    );

                    DebugLogger::log(
                        $this->mappingManualDAO->getIntegration(),
                        sprintf(
                            "Integration to Mautic; syncing %s with a value of %s",
                            $fieldMappingDAO->getInternalField(),
                            var_export($integrationInformationChangeRequest->getNewValue()->getOriginalValue(), true)
                        ),
                        __CLASS__.':'.__FUNCTION__
                    );

                    break;

                case ObjectMappingDAO::SYNC_TO_INTEGRATION:
                    // Ignore this field
                    DebugLogger::log(
                        $this->mappingManualDAO->getIntegration(),
                        sprintf(
                            "Integration to Mautic; the %s object's field %s was ignored because it's configured to sync to the integration",
                            $internalObject->getObject(),
                            $fieldMappingDAO->getInternalField()
                        ),
                        __CLASS__.':'.__FUNCTION__
                    );

                    break;
                case ObjectMappingDAO::SYNC_BIDIRECTIONALLY:
                    try {
                        $internalField = $internalObject->getField($fieldMappingDAO->getInternalField());
                    } catch (FieldNotFoundException $exception) {
                        $internalField = null;
                    }

                    if ($internalField) {
                        $internalInformationChangeRequest = new InformationChangeRequestDAO(
                            MauticSyncDataExchange::NAME,
                            $internalObject->getObject(),
                            $internalObject->getObjectId(),
                            $internalField->getName(),
                            $internalField->getValue()
                        );
                        $internalInformationChangeRequest->setPossibleChangeDateTime($internalObject->getChangeDateTime());
                        $internalInformationChangeRequest->setCertainChangeDateTime($internalField->getChangeDateTime());

                        // There is a conflict so let the judge determine which value comes out on top
                        $judgeModes = [
                            SyncJudgeInterface::PRESUMPTION_OF_INNOCENCE_MODE,
                            SyncJudgeInterface::BEST_EVIDENCE_MODE
                        ];

                        foreach ($judgeModes as $judgeMode) {
                            try {
                                $winningChangeRequest = $this->syncJudge->adjudicate(
                                    $judgeMode,
                                    $internalInformationChangeRequest,
                                    $integrationInformationChangeRequest
                                );

                                $objectChange->addField(
                                    new FieldDAO($fieldMappingDAO->getInternalField(), $winningChangeRequest->getNewValue())
                                );

                                DebugLogger::log(
                                    $this->mappingManualDAO->getIntegration(),
                                    sprintf(
                                        "Integration to Mautic; sync judge determined to sync %s to the %s object's field %s with a value of %s using the %s judging mode",
                                        $winningChangeRequest->getIntegration(),
                                        $winningChangeRequest->getObject(),
                                        $fieldMappingDAO->getInternalField(),
                                        var_export($winningChangeRequest->getNewValue()->getOriginalValue(), true),
                                        $judgeMode
                                    ),
                                    __CLASS__.':'.__FUNCTION__
                                );

                                break;
                            } catch (ConflictUnresolvedException $ex) {
                                DebugLogger::log(
                                    $this->mappingManualDAO->getIntegration(),
                                    sprintf(
                                        "Integration to Mautic; no winner was determined using the %s judging mode for object %s field %s",
                                        $judgeMode,
                                        $internalObject->getObject(),
                                        $fieldMappingDAO->getInternalField()
                                    ),
                                    __CLASS__.':'.__FUNCTION__
                                );

                                continue;
                            }
                        }

                        continue;
                    }

                    $objectChange->addField(
                        new FieldDAO(
                            $fieldMappingDAO->getInternalField(),
                            $integrationInformationChangeRequest->getNewValue()
                        )
                    );

                    DebugLogger::log(
                        $this->mappingManualDAO->getIntegration(),
                        sprintf(
                            "Integration to Mautic; the sync is bidirectional but no conflicts were found so syncing the %s object's field %s with a value of %s",
                            $internalObject->getObject(),
                            $fieldMappingDAO->getInternalField(),
                            var_export($integrationInformationChangeRequest->getNewValue()->getOriginalValue(), true)
                        ),
                        __CLASS__.':'.__FUNCTION__
                    );

                    break;
            }
        }

        // Set the change date/time from the object so that we can update last sync date based on this
        $objectChange->setChangeDateTime($integrationObject->getChangeDateTime());

        return $objectChange;
    }

    /**
     * @param ReportDAO        $syncReport
     * @param ObjectMappingDAO $objectMapping
     * @param ReportObjectDAO  $internalObject
     * @param ReportObjectDAO  $integrationObject
     *
     * @return ObjectChangeDAO
     * @throws ObjectNotFoundException
     */
    private function getSyncObjectChangeMauticToIntegration(
        ReportDAO $syncReport,
        ObjectMappingDAO $objectMapping,
        ReportObjectDAO $internalObject,
        ReportObjectDAO $integrationObject
    ) {
        $objectChange = new ObjectChangeDAO(
            $syncReport->getIntegration(),
            $integrationObject->getObject(),
            $integrationObject->getObjectId(),
            $internalObject->getObject(),
            $internalObject->getObjectId()
        );

        if ($integrationObject->getObjectId()) {
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Mautic to integration; found a match between the integration %s:%s object and Mautic's %s:%s object",
                    $integrationObject->getObject(),
                    (string) $integrationObject->getObjectId(),
                    $internalObject->getObject(),
                    (string) $internalObject->getObjectId()
                ),
                __CLASS__.':'.__FUNCTION__
            );
        } else {
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Mautic to integration: no match found for %s:%s",
                    $internalObject->getObject(),
                    (string) $internalObject->getObjectId()
                ),
                __CLASS__.':'.__FUNCTION__
            );
        }


        /** @var FieldMappingDAO[] $fieldMappings */
        $fieldMappings = $objectMapping->getFieldMappings();
        foreach ($fieldMappings as $fieldMappingDAO) {
            try {
                $internalInformationChangeRequest = $syncReport->getInformationChangeRequest(
                    $internalObject->getObject(),
                    $internalObject->getObjectId(),
                    $fieldMappingDAO->getInternalField()
                );
            } catch (FieldNotFoundException $e) {
                continue;
            }

            // Perform the sync in the direction instructed
            switch ($fieldMappingDAO->getSyncDirection()) {
                case ObjectMappingDAO::SYNC_TO_MAUTIC:
                    // Ignore this field
                    DebugLogger::log(
                        $this->mappingManualDAO->getIntegration(),
                        sprintf(
                            "Mautic to integration; the %s object's field %s ignored because it's configured to sync to Mautic",
                            $integrationObject->getObject(),
                            $fieldMappingDAO->getIntegrationField()
                        ),
                        __CLASS__.':'.__FUNCTION__
                    );

                    break;
                case ObjectMappingDAO::SYNC_TO_INTEGRATION:
                case ObjectMappingDAO::SYNC_BIDIRECTIONALLY:
                    // Bidirectional conflicts were handled by getSyncObjectChangeIntegrationToMautic
                    $objectChange->addField(
                        new FieldDAO($fieldMappingDAO->getIntegrationField(), $internalInformationChangeRequest->getNewValue())
                    );

                    DebugLogger::log(
                        $this->mappingManualDAO->getIntegration(),
                        sprintf(
                            "Mautic to integration; syncing %s object's field %s with a value of %s",
                            $integrationObject->getObject(),
                            $fieldMappingDAO->getIntegrationField(),
                            var_export($internalInformationChangeRequest->getNewValue()->getOriginalValue(), true)
                        ),
                        __CLASS__.':'.__FUNCTION__
                    );

                    break;
            }
        }

        // Set the change date/time from the object so that we can update last sync date based on this
        $objectChange->setChangeDateTime($internalObject->getChangeDateTime());

        return $objectChange;
    }
}
