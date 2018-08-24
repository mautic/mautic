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

use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\FieldMappingDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\SyncDate\SyncDateHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;

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
     * @var \DateTimeInterface
     */
    private $syncDateTime;

    /**
     * @var bool
     */
    private $isFirstTimeSync = false;

    /**
     * @var \DateTimeInterface|null
     */
    private $syncFromDateTime;

    /**
     * @var \DateTimeInterface[]
     */
    private $lastObjectSyncDates = [];

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
     * @param bool                      $isFirstTimeSync
     * @param \DateTimeInterface|null   $syncFromDateTime
     */
    public function __construct(
        SyncJudgeInterface $syncJudge,
        MappingManualDAO $mappingManualDAO,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncDataExchangeInterface $integrationSyncDataExchange,
        SyncDateHelper $syncDateHelper,
        $isFirstTimeSync,
        \DateTimeInterface $syncFromDateTime = null
    ) {
        $this->syncJudge                   = $syncJudge;
        $this->mappingManualDAO            = $mappingManualDAO;
        $this->internalSyncDataExchange    = $internalSyncDataExchange;
        $this->integrationSyncDataExchange = $integrationSyncDataExchange;
        $this->mappingManualDAO            = $mappingManualDAO;
        $this->syncDateHelper              = $syncDateHelper;
        $this->isFirstTimeSync             = $isFirstTimeSync;
        $this->syncFromDateTime            = $syncFromDateTime;
    }

    /**
     * Execute sync with integration
     */
    public function execute()
    {
        defined('MAUTIC_INTEGRATION_ACTIVE_SYNC') or define('MAUTIC_INTEGRATION_ACTIVE_SYNC', 1);

        $this->syncDateTime = new \DateTimeImmutable();

        $this->syncIteration = 1;
        do {
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf("Integration to Mautic; syncing iteration %s", $this->syncIteration),
                __CLASS__.':'.__FUNCTION__
            );

            $syncReport = $this->generateIntegrationSyncReport();
            if ($syncReport->shouldSync()) {
                // Convert the integrations' report into an "order" or instructions for Mautic
                $syncOrder = $this->generateInternalSyncOrder($syncReport);
                if (!$syncOrder->shouldSync()) {
                    DebugLogger::log(
                        $this->mappingManualDAO->getIntegration(),
                        "Integration to Mautic; no object changes were recorded possible due to field direction configurations",
                        __CLASS__.':'.__FUNCTION__
                    );

                    continue;
                }

                // Execute the sync instructions
                $this->internalSyncDataExchange->executeSyncOrder($syncOrder);
                // Fetch the next iteration/batch
                $this->syncIteration++;
            }
        } while ($syncReport->shouldSync());

        $this->syncIteration = 1;
        do {
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf("Mautic to integration; syncing iteration %s", $this->syncIteration),
                __CLASS__.':'.__FUNCTION__
            );

            $syncReport = $this->generateInternalSyncReport();
            if ($syncReport->shouldSync()) {
                // Convert the internal report into an "order" or instructions for the integration
                $syncOrder = $this->generateIntegrationSyncOrder($syncReport);
                if (!$syncOrder->shouldSync()) {
                    DebugLogger::log(
                        $this->mappingManualDAO->getIntegration(),
                        "Mautic to integration; no object changes were recorded possible due to field direction configurations",
                        __CLASS__.':'.__FUNCTION__
                    );

                    continue;
                }

                // Execute the sync instructions
                $this->integrationSyncDataExchange->executeSyncOrder($syncOrder);
                // Save the mappings between Mautic objects and the integration's objects
                $this->internalSyncDataExchange->saveObjectMappings($syncOrder->getObjectMappings());
                // Fetch the next iteration/batch
                $this->syncIteration++;
            }
        } while ($syncReport->shouldSync());
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

            $objectSyncFromDateTime = $this->getSyncFromDateTime($this->mappingManualDAO->getIntegration(), $integrationObjectName);
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Integration to Mautic; syncing from %s for the %s object with %d fields",
                    $objectSyncFromDateTime->format('Y-m-d H:i:s'),
                    $integrationObjectName,
                    count($integrationObjectFields)
                ),
                __CLASS__.':'.__FUNCTION__
            );

            $integrationRequestObject = new RequestObjectDAO($integrationObjectName, $objectSyncFromDateTime, $this->syncDateTime);
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

            $objectSyncFromDateTime = $this->getSyncFromDateTime(MauticSyncDataExchange::NAME, $internalObjectName);
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Mautic to integration; syncing from %s for the %s object with %d fields",
                    $objectSyncFromDateTime->format('Y-m-d H:i:s'),
                    $internalObjectName,
                    count($internalObjectFields)
                ),
                __CLASS__.':'.__FUNCTION__
            );

            $internalRequestObject  = new RequestObjectDAO($internalObjectName, $objectSyncFromDateTime, $this->syncDateTime);
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
        $syncOrder = new OrderDAO($this->syncDateTime, $this->isFirstTimeSync);

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
        $syncOrder = new OrderDAO($this->syncDateTime, $this->isFirstTimeSync);

        $internalObjectNames = $this->mappingManualDAO->getInternalObjectsNames();
        foreach ($internalObjectNames as $internalObjectName) {
            $internalObjects              = $syncReport->getObjects($internalObjectName);
            $mappedIntegrationObjectNames = $this->mappingManualDAO->getMappedIntegrationObjectsNames($internalObjectName);

            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Mautic to integration; found %d objects for the %s object mapped to the %s integration object(s)",
                    count($internalObjects),
                    $internalObjectName,
                    implode(", ", $mappedIntegrationObjectNames)
                ),
                __CLASS__.':'.__FUNCTION__
            );

            foreach ($mappedIntegrationObjectNames as $mappedIntegrationObjectName) {
                $objectMapping = $this->mappingManualDAO->getObjectMapping($internalObjectName, $mappedIntegrationObjectName);
                foreach ($internalObjects as $internalObject) {
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
                }
            }
        }

        return $syncOrder;
    }

    /**
     * @param string $integration
     * @param string $object
     *
     * @return \DateTimeInterface
     * @throws \Exception
     */
    private function getSyncFromDateTime(string $integration, string $object): \DateTimeInterface
    {
        if ($this->syncFromDateTime) {
            // The command requested a specific start date so use it

            return $this->syncFromDateTime;
        }

        $key = $integration.$object;
        if (isset($this->lastObjectSyncDates[$key])) {
            // Use the same sync date for integrations to paginate properly

            return $this->lastObjectSyncDates[$key];
        }

        if (MauticSyncDataExchange::NAME !== $integration && $lastSync = $this->syncDateHelper->getLastSyncDateForObject($integration, $object)) {
            // Use the latest sync date recorded
            $this->lastObjectSyncDates[$key] = new \DateTimeImmutable($lastSync, new \DateTimeZone('UTC'));
        } else {
            // Otherwise, just sync the last 24 hours
            $this->lastObjectSyncDates[$key] = new \DateTimeImmutable('-24 hours', new \DateTimeZone('UTC'));
        }

        return $this->lastObjectSyncDates[$key];
    }

    /**
     * Generates a ObjectChangeDAO from Integration to Mautic
     *
     * @param ReportDAO        $syncReport
     * @param ObjectMappingDAO $objectMapping
     * @param ReportObjectDAO  $integrationObject
     * @param ReportObjectDAO  $internalObject
     *
     * @return ObjectChangeDAO
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
                    "Integration to Mautic; found a match between Mautic's %s:%s object adn the integration %s:%s object ",
                    $internalObject,
                    (string) $internalObject->getObjectId(),
                    $integrationObject->getObject(),
                    (string) $integrationObject->getObjectId()
                ),
                __CLASS__.':'.__FUNCTION__
            );
        }

        /** @var FieldMappingDAO[] $fieldMappings */
        $fieldMappings = $objectMapping->getFieldMappings();
        foreach ($fieldMappings as $fieldMappingDAO) {
            $integrationInformationChangeRequest = $syncReport->getInformationChangeRequest(
                $integrationObject->getObject(),
                $integrationObject->getObjectId(),
                $fieldMappingDAO->getIntegrationField()
            );

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
                            $integrationInformationChangeRequest->getNewValue()->getOriginalValue()
                        ),
                        __CLASS__.':'.__FUNCTION__
                    );

                    break;

                case ObjectMappingDAO::SYNC_TO_INTEGRATION:
                    // Ignore this field
                    DebugLogger::log(
                        $this->mappingManualDAO->getIntegration(),
                        sprintf(
                            "Integration to Mautic; the %s field for the %s object's ignored because it's configured to sync to the integration",
                            $fieldMappingDAO->getInternalField(),
                            $internalObject->getObject()
                        ),
                        __CLASS__.':'.__FUNCTION__
                    );

                    break;
                case ObjectMappingDAO::SYNC_BIDIRECTIONALLY:
                    if ($internalField = $internalObject->getField($fieldMappingDAO->getInternalField())) {
                        $internalInformationChangeRequest = new InformationChangeRequestDAO(
                            MauticSyncDataExchange::NAME,
                            $internalObject->getObject(),
                            $internalObject->getObjectId(),
                            $internalField->getName(),
                            $internalField->getValue()
                        );

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
                                        "Integration to Mautic; sync judge determined to sync %s to field %s with a value of %s using the %s judging mode",
                                        $winningChangeRequest->getIntegration(),
                                        $fieldMappingDAO->getInternalField(),
                                        $winningChangeRequest->getNewValue()->getOriginalValue(),
                                        $judgeMode
                                    ),
                                    __CLASS__.':'.__FUNCTION__
                                );

                                break;
                            } catch (\LogicException $ex) {
                                DebugLogger::log(
                                    $this->mappingManualDAO->getIntegration(),
                                    sprintf(
                                        "Integration to Mautic; no winner was determined using the %s judging mode for field %s",
                                        $judgeMode,
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
                            "Integration to Mautic; the sync is bidirectional but no conflicts were found so syncing %s with a value of %s",
                            $fieldMappingDAO->getInternalField(),
                            $integrationInformationChangeRequest->getNewValue()->getOriginalValue()
                        ),
                        __CLASS__.':'.__FUNCTION__
                    );

                    break;
            }
        }

        return $objectChange;
    }

    /**
     * @param ReportDAO        $syncReport
     * @param ObjectMappingDAO $objectMapping
     * @param ReportObjectDAO  $internalObject
     * @param ReportObjectDAO  $integrationObject
     *
     * @return ObjectChangeDAO
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
                    $internalObject,
                    (string) $internalObject->getObjectId()
                ),
                __CLASS__.':'.__FUNCTION__
            );
        }

        /** @var FieldMappingDAO[] $fieldMappings */
        $fieldMappings = $objectMapping->getFieldMappings();
        foreach ($fieldMappings as $fieldMappingDAO) {
            $internalInformationChangeRequest = $syncReport->getInformationChangeRequest(
                $internalObject->getObject(),
                $internalObject->getObjectId(),
                $fieldMappingDAO->getInternalField()
            );

            // Perform the sync in the direction instructed
            switch ($fieldMappingDAO->getSyncDirection()) {
                case ObjectMappingDAO::SYNC_TO_MAUTIC:
                    // Ignore this field
                    DebugLogger::log(
                        $this->mappingManualDAO->getIntegration(),
                        sprintf(
                            "Mautic to integration; the %s field for the %s object's ignored because it's configured to sync to Mautic",
                            $fieldMappingDAO->getIntegrationField(),
                            $integrationObject->getObject()
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
                            "Mautic to integration; syncing %s with a value of %s",
                            $fieldMappingDAO->getIntegrationField(),
                            $internalInformationChangeRequest->getNewValue()->getOriginalValue()
                        ),
                        __CLASS__.':'.__FUNCTION__
                    );

                    break;
            }
        }

        return $objectChange;
    }
}
