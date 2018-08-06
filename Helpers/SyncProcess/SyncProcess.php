<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Helpers\SyncProcess;

use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\ObjectMappingDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order\FieldDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\FieldMappingDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order\OrderDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report\ReportDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Request\RequestDAO;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\MauticIntegrationsBundle\Helpers\SyncJudge\SyncJudgeInterface;

/**
 * Class SyncProcess
 */
class SyncProcess
{
    /**
     * @var SyncJudgeInterface
     */
    private $syncJudgeService;

    /**
     * @var MappingManualDAO
     */
    private $mappingManualDAO;

    /**
     * @var SyncDataExchangeInterface
     */
    private $internalSyncDataExchange;

    /**
     * @var ReportDAO
     */
    private $internalSyncReport;

    /**
     * @var OrderDAO
     */
    private $internalSyncOrder;

    /**
     * @var SyncDataExchangeInterface
     */
    private $integrationSyncDataExchange;

    /**
     * @var ReportDAO
     */
    private $integrationSyncReport;

    /**
     * @var OrderDAO
     */
    private $integrationSyncOrder;

    /**
     * @var int
     */
    private $syncTimestamp;

    /**
     * SyncProcess constructor.
     *
     * @param                             $fromTimestamp
     * @param SyncJudgeInterface          $syncJudgeService
     * @param MappingManualDAO            $mappingManualDAO
     * @param SyncDataExchangeInterface   $internalSyncDataExchange
     * @param SyncDataExchangeInterface   $integrationSyncDataExchange
     */
    public function __construct(
        $fromTimestamp,
        SyncJudgeInterface $syncJudgeService,
        MappingManualDAO $mappingManualDAO,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncDataExchangeInterface $integrationSyncDataExchange
    ) {
        $this->syncJudgeService            = $syncJudgeService;
        $this->mappingManualDAO            = $mappingManualDAO;
        $this->internalSyncDataExchange    = $internalSyncDataExchange;
        $this->integrationSyncDataExchange = $integrationSyncDataExchange;
        $this->mappingManualDAO            = $mappingManualDAO;

        $this->generateInternalSyncReport($fromTimestamp);
        $this->generateIntegrationSyncReport($fromTimestamp);
    }

    /**
     * Execute sync with integration
     */
    public function execute()
    {
        $this->syncTimestamp = (new \DateTime())->getTimestamp();

        // 2. prepare internal sync order based on mapped objects
        // 3. add unmatched integration objects to internal sync order
        // 4. prepare integration sync order based on mapped objects
        // 5. add unmatched integration objects to integration sync order
        // 6. perform the sync
        // 7. process entity mappings

        $this->generateSyncOrders();

        // Execute the syncs
        $this->internalSyncDataExchange->executeSyncOrder($this->internalSyncOrder);
        $this->integrationSyncDataExchange->executeSyncOrder($this->integrationSyncOrder);
    }

    /**
     * @param                  $fromTimestamp
     */
    private function generateInternalSyncReport($fromTimestamp)
    {
        $internalRequestDAO = new RequestDAO($fromTimestamp);

        $internalObjectsNames = $this->mappingManualDAO->getInternalObjectsNames();
        foreach ($internalObjectsNames as $internalObjectName) {
            $internalObjectFields = $this->mappingManualDAO->getInternalObjectFieldNames($internalObjectName);
            if (count($internalObjectFields) === 0) {
                // No fields configured for a sync
                continue;
            }

            $internalRequestObject = new RequestObjectDAO($internalObjectName);
            foreach ($internalObjectFields as $internalObjectField) {
                $internalRequestObject->addField($internalObjectField);
            }
            $internalRequestDAO->addObject($internalRequestObject);
        }

        $this->internalSyncReport = $internalRequestDAO->shouldSync()
            ? $this->internalSyncDataExchange->getSyncReport($internalRequestDAO)
            :
            new ReportDAO(MauticSyncDataExchange::NAME);
    }

    /**
     * @param                  $fromTimestamp
     */
    private function generateIntegrationSyncReport($fromTimestamp)
    {
        $integrationRequestDAO = new RequestDAO($fromTimestamp);

        $integrationObjectsNames = $this->mappingManualDAO->getIntegrationObjectsNames();
        foreach ($integrationObjectsNames as $integrationObjectName) {
            $integrationObjectFields = $this->mappingManualDAO->getIntegrationObjectFieldNames($integrationObjectName);
            if (count($integrationObjectFields) === 0) {
                // No fields configured for a sync
                continue;
            }

            $integrationRequestObject = new RequestObjectDAO($integrationObjectName);
            foreach ($integrationObjectFields as $integrationObjectField) {
                $integrationRequestObject->addField($integrationObjectField);
            }
            $integrationRequestDAO->addObject($integrationRequestObject);
        }

        $this->integrationSyncReport = $integrationRequestDAO->shouldSync()
            ? $this->integrationSyncDataExchange->getSyncReport($integrationRequestDAO)
            :
            new ReportDAO($this->mappingManualDAO->getIntegration());
    }

    private function generateSyncOrders()
    {
        $this->internalSyncOrder    = new OrderDAO($this->syncTimestamp);
        $this->integrationSyncOrder = new OrderDAO($this->syncTimestamp);

        $internalObjectsNames = $this->mappingManualDAO->getInternalObjectsNames();

        // @todo convert to a factory/service/interface that is passed through SyncProcessFactory into this class
        // @todo find matches based on Mautic unique identifiers
        $matchDetectors = [
            MauticSyncDataExchange::CONTACT_OBJECT => new class
            {
                public function isDuplicate(
                    MappingManualDAO $mappingManualDAO,
                    ReportObjectDAO $internalObjectDAO,
                    ReportObjectDAO $integrationObjectDAO
                ) {
                    $internalEmail         = $internalObjectDAO->getField('email')->getValue()->getNormalizedValue();
                    $integrationEmailField = $mappingManualDAO->getIntegrationMappedField(
                        MauticSyncDataExchange::CONTACT_OBJECT,
                        $integrationObjectDAO->getObject(),
                        'email'
                    );
                    $integrationEmail      = $internalObjectDAO->getField($integrationEmailField)->getValue()->getNormalizedValue();

                    return strtolower($internalEmail) === strtolower($integrationEmail);
                }
            }
        ];

        foreach ($internalObjectsNames as $internalObjectName) {
            $internalObjects               = $this->internalSyncReport->getObjects($internalObjectName);
            $mappedIntegrationObjectsNames = $this->mappingManualDAO->getMappedIntegrationObjectsNames($internalObjectName);
            foreach ($mappedIntegrationObjectsNames as $mappedIntegrationObjectName) {
                $objectMappingDAO   = $this->mappingManualDAO->getObjectMapping($internalObjectName, $mappedIntegrationObjectName);
                $integrationObjects = $this->integrationSyncReport->getObjects($mappedIntegrationObjectName);
                do {
                    reset($integrationObjects);
                    /** @var ReportObjectDAO $comparedInternalObject */
                    $comparedInternalObject = current($internalObjects);
                    $mappedIntegrationId    = $objectMappingDAO->getMappedIntegrationObjectId($comparedInternalObject->getObjectId());
                    if ($mappedIntegrationId !== null) {
                        $comparedIntegrationObject = $this->integrationSyncReport->getObject($mappedIntegrationObjectName, $mappedIntegrationId);
                        $this->orderObjectsSync($objectMappingDAO, $comparedInternalObject, $comparedIntegrationObject);

                        continue;
                    }

                    if (array_key_exists($internalObjectName, $matchDetectors)) {
                        $duplicityDetector = $matchDetectors[$internalObjectName];
                        $matches           = [];
                        do {
                            /** @var ReportObjectDAO $comparedIntegrationObject */
                            $comparedIntegrationObject = current($integrationObjects);
                            $mappedInternalId          = $objectMappingDAO->getMappedInternalObjectId($comparedIntegrationObject->getObjectId());
                            if ($mappedInternalId === null) {
                                $isDuplicate = $duplicityDetector->isDuplicate(
                                    $this->mappingManualDAO,
                                    $comparedInternalObject,
                                    $comparedIntegrationObject
                                );
                                if ($isDuplicate) {
                                    $matches[] = $comparedIntegrationObject;
                                }
                            }
                        } while (next($integrationObjects) !== false);

                        if (count($matches) === 0) {
                            // No matches so continue
                            continue;
                        }

                        $integrationObjectMatch = reset($matches);
                        $objectMappingDAO->mapIds($comparedInternalObject->getObjectId(), $integrationObjectMatch->getObjectId());
                        $this->orderObjectsSync($objectMappingDAO, $comparedInternalObject, $integrationObjectMatch);

                        continue;
                    }

                    // Add internal object to integration sync order
                    $this->addToIntegrationSyncOrder($objectMappingDAO, $comparedInternalObject);
                } while (next($internalObjects) !== false);
            }
        }

        $integrationObjectsNames = $this->mappingManualDAO->getIntegrationObjectsNames();
        foreach ($integrationObjectsNames as $integrationObjectName) {
            $integrationObjects         = $this->integrationSyncReport->getObjects($integrationObjectName);
            $mappedInternalObjectsNames = $this->mappingManualDAO->getMappedInternalObjectsNames($integrationObjectName);
            foreach ($mappedInternalObjectsNames as $mappedInternalObjectName) {
                $objectMappingDAO = $this->mappingManualDAO->getObjectMapping($mappedInternalObjectName, $integrationObjectName);
                foreach ($integrationObjects as $integrationObject) {
                    $mappedInternalObjectId = $objectMappingDAO->getMappedInternalObjectId($integrationObject->getObjectId());
                    if ($mappedInternalObjectId !== null) {
                        continue;
                    }

                    // Object is new in integration and not matched
                    $this->addToInternalSyncOrder($objectMappingDAO, $integrationObject);
                }
            }
        }
    }

    /**
     * @param ObjectMappingDAO     $objectMappingDAO
     * @param ReportObjectDAO|null $internalObjectDAO
     * @param ReportObjectDAO|null $integrationObjectDAO
     */
    private function orderObjectsSync(
        ObjectMappingDAO $objectMappingDAO,
        ReportObjectDAO $internalObjectDAO,
        ReportObjectDAO $integrationObjectDAO
    ) {
        $integrationObjectChange = new ObjectChangeDAO(
            $objectMappingDAO->getIntegrationObjectName(),
            $integrationObjectDAO->getObjectId(),
            $internalObjectDAO->getObject(),
            $internalObjectDAO->getObjectId()
        );

        $internalObjectChange = new ObjectChangeDAO(
            $objectMappingDAO->getInternalObjectName(),
            $internalObjectDAO->getObjectId(),
            $integrationObjectDAO->getObject(),
            $integrationObjectDAO->getObjectId()
        );

        /** @var FieldMappingDAO[] $fieldMappings */
        $fieldMappings = $objectMappingDAO->getFieldMappings();
        foreach ($fieldMappings as $fieldMappingDAO) {
            $internalInformationChangeRequest = $this->internalSyncReport->getInformationChangeRequest(
                $objectMappingDAO->getInternalObjectName(),
                $internalObjectDAO->getObjectId(),
                $fieldMappingDAO->getInternalField()
            );

            $integrationInformationChangeRequest = $this->integrationSyncReport->getInformationChangeRequest(
                $objectMappingDAO->getIntegrationObjectName(),
                $integrationObjectDAO->getObjectId(),
                $fieldMappingDAO->getIntegrationField()
            );

            // Perform the sync in the direction instructed
            switch ($fieldMappingDAO->getSyncDirection()) {
                case ObjectMappingDAO::SYNC_TO_MAUTIC:
                    $internalFieldChange = new FieldDAO($fieldMappingDAO->getInternalField(), $integrationInformationChangeRequest->getNewValue());
                    $internalObjectChange->addField($internalFieldChange);

                    break;
                case ObjectMappingDAO::SYNC_TO_INTEGRATION:
                    $integrationFieldChange = new FieldDAO($fieldMappingDAO->getIntegrationField(), $internalInformationChangeRequest->getNewValue());
                    $integrationObjectChange->addField($integrationFieldChange);

                    break;
                case ObjectMappingDAO::SYNC_BIDIRECTIONALLY:
                    $judgeModes = [
                        SyncJudgeInterface::PRESUMPTION_OF_INNOCENCE_MODE,
                        SyncJudgeInterface::BEST_EVIDENCE_MODE
                    ];
                    foreach ($judgeModes as $judgeMode) {
                        try {
                            $result              = $this->syncJudgeService->adjudicate(
                                $judgeMode,
                                $internalInformationChangeRequest,
                                $integrationInformationChangeRequest
                            );
                            $internalFieldChange = new FieldDAO($fieldMappingDAO->getInternalField(), $result);
                            $internalObjectChange->addField($internalFieldChange);
                            $integrationFieldChange = new FieldDAO($fieldMappingDAO->getIntegrationField(), $result);
                            $integrationObjectChange->addField($integrationFieldChange);
                            break;
                        } catch (\LogicException $ex) {
                            continue;
                        }
                    }
            }
        }

        $this->internalSyncOrder->addObjectChange($internalObjectChange);
        $this->integrationSyncOrder->addObjectChange($integrationObjectChange);
    }

    private function addToIntegrationSyncOrder(ObjectMappingDAO $objectMappingDAO, ReportObjectDAO $internalObjectDAO)
    {
        $integrationObjectChange = new ObjectChangeDAO(
            $objectMappingDAO->getIntegrationObjectName(),
            null,
            $internalObjectDAO->getObject(),
            $internalObjectDAO->getObjectId()
        );

        /** @var FieldMappingDAO[] $fieldMappings */
        $fieldMappings = $objectMappingDAO->getFieldMappings();
        foreach ($fieldMappings as $fieldMappingDAO) {
            $internalInformationChangeRequest = $this->internalSyncReport->getInformationChangeRequest(
                $objectMappingDAO->getInternalObjectName(),
                $internalObjectDAO->getObjectId(),
                $fieldMappingDAO->getInternalField()
            );

            // Perform the sync in the direction instructed
            switch ($fieldMappingDAO->getSyncDirection()) {
                case ObjectMappingDAO::SYNC_TO_MAUTIC:
                    // Ignore this field
                    break;
                case ObjectMappingDAO::SYNC_BIDIRECTIONALLY:
                case ObjectMappingDAO::SYNC_TO_INTEGRATION:
                    $integrationFieldChange = new FieldDAO($fieldMappingDAO->getIntegrationField(), $internalInformationChangeRequest->getNewValue());
                    $integrationObjectChange->addField($integrationFieldChange);

                    break;
            }
        }

        $this->integrationSyncOrder->addObjectChange($integrationObjectChange);
    }

    /**
     * @param ObjectMappingDAO $objectMappingDAO
     * @param ReportObjectDAO  $integrationObjectDAO
     */
    private function addToInternalSyncOrder(ObjectMappingDAO $objectMappingDAO, ReportObjectDAO $integrationObjectDAO)
    {
        $internalObjectChange = new ObjectChangeDAO(
            $objectMappingDAO->getInternalObjectName(),
            null,
            $integrationObjectDAO->getObject(),
            $integrationObjectDAO->getObjectId()
        );

        /** @var FieldMappingDAO[] $fieldMappings */
        $fieldMappings = $objectMappingDAO->getFieldMappings();
        foreach ($fieldMappings as $fieldMappingDAO) {
            $integrationInformationChangeRequest = $this->integrationSyncReport->getInformationChangeRequest(
                $objectMappingDAO->getIntegrationObjectName(),
                $integrationObjectDAO->getObjectId(),
                $fieldMappingDAO->getIntegrationField()
            );

            // Perform the sync in the direction instructed
            switch ($fieldMappingDAO->getSyncDirection()) {
                case ObjectMappingDAO::SYNC_TO_INTEGRATION:
                    // Ignore this field

                    break;
                case ObjectMappingDAO::SYNC_TO_MAUTIC:
                case ObjectMappingDAO::SYNC_BIDIRECTIONALLY:

                    $internalFieldChange = new FieldDAO($fieldMappingDAO->getInternalField(), $integrationInformationChangeRequest->getNewValue());
                    $internalObjectChange->addField($internalFieldChange);

                    break;
            }
        }

        $this->internalSyncOrder->addObjectChange($internalObjectChange);
    }
}
