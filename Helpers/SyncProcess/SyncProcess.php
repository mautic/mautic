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
     * @param int                       $fromTimestamp
     * @param SyncJudgeInterface        $syncJudgeService
     * @param MappingManualDAO          $mappingManualDAO
     * @param SyncDataExchangeInterface $internalSyncDataExchange
     * @param SyncDataExchangeInterface $integrationSyncDataExchange
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

        $internalRequestDAO   = new RequestDAO($fromTimestamp);
        $internalObjectsNames = $mappingManualDAO->getInternalObjectsNames();
        foreach ($internalObjectsNames as $internalObjectName) {
            $internalRequestObject = new RequestObjectDAO($internalObjectName);
            $internalObjectFields  = $mappingManualDAO->getInternalObjectFieldNames($internalObjectName);
            foreach ($internalObjectFields as $internalObjectField) {
                $internalRequestObject->addField($internalObjectField);
            }
            $internalRequestDAO->addObject($internalRequestObject);
        }
        $this->internalSyncReport = $this->internalSyncDataExchange->getSyncReport($internalRequestDAO);

        $integrationRequestDAO   = new RequestDAO($fromTimestamp);
        $integrationObjectsNames = $mappingManualDAO->getIntegrationObjectsNames();
        foreach ($integrationObjectsNames as $integrationObjectName) {
            $integrationRequestObject = new RequestObjectDAO($integrationObjectName);
            $integrationObjectFields  = $mappingManualDAO->getIntegrationObjectFieldNames($integrationObjectName);
            foreach ($integrationObjectFields as $integrationObjectField) {
                $integrationRequestObject->addField($integrationObjectField);
            }
            $integrationRequestDAO->addObject($integrationRequestObject);
        }
        $this->integrationSyncReport = $this->integrationSyncDataExchange->getSyncReport($integrationRequestDAO);
    }

    /**
     * Execute sync with integration
     */
    public function execute()
    {
        $this->syncTimestamp = (new \DateTime())->getTimestamp();

        // 1. get map of Mautic matches from integration sync report based on already known matches then by unique identifiers
        // 2. prepare internal sync order based on mapped objects
        // 3. prepare integration sync order based on mapped objects
        // 4. add unmatched integration objects to internal sync order
        // 5. add unmatched integration objects to integration sync order
        // 6. perform the sync
        // 7. process entity mappings

        // Prepare the orders
        $this->prepareInternalSyncOrder();
        $this->prepareIntegrationSyncOrder();

        // Execute the syncs
        $this->internalSyncDataExchange->executeSyncOrder($this->internalSyncOrder);
        $this->integrationSyncDataExchange->executeSyncOrder($this->integrationSyncOrder);
    }

    private function prepareInternalSyncOrder()
    {
        $this->internalSyncOrder = new OrderDAO($this->syncTimestamp);
        $internalObjectsNames    = $this->mappingManualDAO->getInternalObjectsNames();
        foreach ($internalObjectsNames as $internalObjectName) {
            $mappedIntegrationObjectsNames = $this->mappingManualDAO->getMappedIntegrationObjectsNames($internalObjectName);

            foreach ($mappedIntegrationObjectsNames as $mappedIntegrationObjectName) {
                $objectMappingDAO = $this->mappingManualDAO->getObjectMapping($internalObjectName, $mappedIntegrationObjectName);

                $this->internalSyncReport->getObjects($internalObjectName);
                $this->orderObjectsSync($objectMappingDAO, $internalObjectName, $mappedIntegrationObjectName);
            }
        }
    }

    private function prepareIntegrationSyncOrder()
    {
        $this->integrationSyncOrder = new OrderDAO($this->syncTimestamp);
        $integrationObjectNames     = $this->mappingManualDAO->getIntegrationObjectsNames();
        foreach ($integrationObjectNames as $integrationObjectName) {
            $mappedInternalObjectsNames = $this->mappingManualDAO->getMappedInternalObjectsNames($integrationObjectName);

            foreach ($mappedInternalObjectsNames as $mappedInternalObjectsName) {
                $objectMappingDAO = $this->mappingManualDAO->getObjectMapping($mappedInternalObjectsName, $integrationObjectName);

                $this->orderObjectsSync($objectMappingDAO, $mappedInternalObjectsName, $integrationObjectName);
            }
        }
    }

    /**
     * @param ObjectMappingDAO $objectMappingDAO
     * @param ReportObjectDAO  $internalObjectDAO
     * @param ReportObjectDAO  $integrationObjectDAO
     */
    private function orderObjectsSync(ObjectMappingDAO $objectMappingDAO, ReportObjectDAO $internalObjectDAO, ReportObjectDAO $integrationObjectDAO)
    {
        $internalObjectChange    = new ObjectChangeDAO(
            $objectMappingDAO->getInternalObjectName(),
            $internalObjectDAO->getObjectId(),
            $integrationObjectDAO->getObject(),
            $integrationObjectDAO->getObjectId()
        );
        $integrationObjectChange = new ObjectChangeDAO(
            $objectMappingDAO->getIntegrationObjectName(),
            $integrationObjectDAO->getObjectId(),
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
                default:
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

            $this->internalSyncOrder->addObjectChange($internalObjectChange);
            $this->integrationSyncOrder->addObjectChange($integrationObjectChange);
        }
    }
}
