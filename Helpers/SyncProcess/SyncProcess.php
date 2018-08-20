<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Helpers\SyncProcess;

use MauticPlugin\IntegrationsBundle\DAO\Mapping\ObjectMappingDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\InformationChangeRequestDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Order\FieldDAO;
use MauticPlugin\IntegrationsBundle\DAO\Mapping\FieldMappingDAO;
use MauticPlugin\IntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Request\RequestDAO;
use MauticPlugin\IntegrationsBundle\Facade\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Facade\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Helpers\SyncDateHelper;
use MauticPlugin\IntegrationsBundle\Helpers\SyncJudge\SyncJudgeInterface;

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
     * @param \DateTimeInterface|null   $syncFromDateTime
     */
    public function __construct(
        SyncJudgeInterface $syncJudge,
        MappingManualDAO $mappingManualDAO,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncDataExchangeInterface $integrationSyncDataExchange,
        SyncDateHelper $syncDateHelper,
        \DateTimeInterface $syncFromDateTime = null
    ) {
        $this->syncJudge                   = $syncJudge;
        $this->mappingManualDAO            = $mappingManualDAO;
        $this->internalSyncDataExchange    = $internalSyncDataExchange;
        $this->integrationSyncDataExchange = $integrationSyncDataExchange;
        $this->mappingManualDAO            = $mappingManualDAO;
        $this->syncDateHelper              = $syncDateHelper;
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
            $syncReport = $this->generateIntegrationSyncReport();

            if ($syncReport->shouldSync()) {
                // Convert the integrations' report into an "order" or instructions for Mautic
                $syncOrder = $this->generateInternalSyncOrder($syncReport);
                // Execute the sync instructions
                $this->internalSyncDataExchange->executeSyncOrder($syncOrder);
            }

            $this->syncIteration++;
        } while ($syncReport->shouldSync());

        do {
            $syncReport = $this->generateInternalSyncReport();

            if ($syncReport->shouldSync()) {
                $syncOrder = $this->generateIntegrationSyncOrder($syncReport);
                $this->integrationSyncDataExchange->executeSyncOrder($syncOrder);

                $this->internalSyncDataExchange->saveObjectMappings($syncOrder->getEntityMappings());
            }
        } while ($syncReport->shouldSync());
    }

    /**
     * @return ReportDAO
     * @throws \Exception
     */
    private function generateIntegrationSyncReport()
    {
        $integrationRequestDAO = new RequestDAO($this->syncIteration);

        $integrationObjectsNames = $this->mappingManualDAO->getIntegrationObjectsNames();
        foreach ($integrationObjectsNames as $integrationObjectName) {
            $integrationObjectFields = $this->mappingManualDAO->getIntegrationObjectFieldNames($integrationObjectName);
            if (count($integrationObjectFields) === 0) {
                // No fields configured for a sync
                continue;
            }

            $objectSyncFromDateTime   = $this->getSyncFromDateTime($this->mappingManualDAO->getIntegration(), $integrationObjectName);
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
     */
    private function generateInternalSyncReport()
    {
        $internalRequestDAO = new RequestDAO($this->syncIteration);

        $internalObjectsNames = $this->mappingManualDAO->getInternalObjectsNames();
        foreach ($internalObjectsNames as $internalObjectName) {
            $internalObjectFields = $this->mappingManualDAO->getInternalObjectFieldNames($internalObjectName);
            if (count($internalObjectFields) === 0) {
                // No fields configured for a sync
                continue;
            }

            // Sync date does not matter in this case because Mautic will simply process anything in the queue
            $internalRequestObject = new RequestObjectDAO($internalObjectName);
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
     */
    private function generateInternalSyncOrder(ReportDAO $syncReport)
    {
        $syncOrder = new OrderDAO($this->syncDateTime);

        $integrationObjectsNames = $this->mappingManualDAO->getIntegrationObjectsNames();
        foreach ($integrationObjectsNames as $integrationObjectName) {
            $integrationObjects         = $syncReport->getObjects($integrationObjectName);
            $mappedInternalObjectsNames = $this->mappingManualDAO->getMappedInternalObjectsNames($integrationObjectName);
            foreach ($mappedInternalObjectsNames as $mappedInternalObjectName) {
                $objectMapping = $this->mappingManualDAO->getObjectMapping($mappedInternalObjectName, $integrationObjectName);
                foreach ($integrationObjects as $integrationObject) {
                    $internalObject = $this->internalSyncDataExchange->getConflictedInternalObject($this->mappingManualDAO, $mappedInternalObjectName, $integrationObject);
                    $objectChange   = $this->getSyncObjectChangeIntegrationToMautic($syncReport, $objectMapping, $integrationObject, $internalObject);

                    $syncOrder->addObjectChange($objectChange);
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
     */
    private function generateIntegrationSyncOrder(ReportDAO $syncReport)
    {
        $syncOrder = new OrderDAO($this->syncDateTime);

        $internalObjectNames = $this->mappingManualDAO->getIntegrationObjectsNames();
        foreach ($internalObjectNames as $internalObjectName) {
            $internalObjects              = $syncReport->getObjects($internalObjectName);
            $mappedIntegrationObjectNames = $this->mappingManualDAO->getMappedIntegrationObjectsNames($internalObjectName);
            foreach ($mappedIntegrationObjectNames as $mappedIntegrationObjectName) {
                $objectMapping = $this->mappingManualDAO->getObjectMapping($mappedIntegrationObjectName, $internalObjectName);
                foreach ($internalObjects as $internalObject) {
                    $integrationObject = $this->internalSyncDataExchange->getMappedIntegrationObject($mappedIntegrationObjectName, $internalObject);
                    $objectChange = $this->getSyncObjectChangeMauticToIntegration($syncReport, $objectMapping, $internalObject, $integrationObject);

                    $syncOrder->addObjectChange($objectChange);
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

        if ($lastSync = $this->syncDateHelper->getLastSyncDateForObject($integration, $object)) {
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
            $objectMapping->getInternalObjectName(),
            $internalObject->getObject(),
            $integrationObject->getObject(),
            $integrationObject->getObjectId()
        );

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

                    break;

                case ObjectMappingDAO::SYNC_TO_INTEGRATION:
                    // Ignore this field

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
                                break;
                            } catch (\LogicException $ex) {
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
            $objectMapping->getIntegrationObjectName(),
            $integrationObject->getObjectId(),
            $internalObject->getObject(),
            $internalObject->getObjectId()
        );

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

                    break;
                case ObjectMappingDAO::SYNC_TO_INTEGRATION:
                case ObjectMappingDAO::SYNC_BIDIRECTIONALLY:
                    // Bidirectional conflicts were handled by getSyncObjectChangeIntegrationToMautic
                    $objectChange->addField(
                        new FieldDAO($fieldMappingDAO->getInternalField(), $internalInformationChangeRequest->getNewValue())
                    );

                    break;
            }
        }

        return $objectChange;
    }
}
