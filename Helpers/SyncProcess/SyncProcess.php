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
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService\SyncDataExchangeInterface;
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

        $integrationRequestDAO    = new RequestDAO($fromTimestamp);
        $integrationObjectsNames  = $mappingManualDAO->getIntegrationObjectsNames();
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
        $syncTimestamp              = 0;
        $this->internalSyncOrder    = new OrderDAO($syncTimestamp);
        $this->integrationSyncOrder = new OrderDAO($syncTimestamp);
        $internalObjectsNames       = $this->mappingManualDAO->getInternalObjectsNames();
        $duplicityDetectors         = [

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

                    } else {
                        if (array_key_exists($internalObjectName, $duplicityDetectors)) {
                            $duplicityDetector = $duplicityDetectors[$internalObjectName];
                            $duplicities       = [];
                            do {
                                /** @var ReportObjectDAO $comparedIntegrationObject */
                                $comparedIntegrationObject = current($integrationObjects);
                                $mappedInternalId          = $objectMappingDAO->getMappedInternalObjectId($comparedIntegrationObject->getObjectId());
                                if ($mappedInternalId === null) {
                                    $isDuplicate = $duplicityDetector->isDuplicate($comparedInternalObject, $comparedIntegrationObject);
                                    if ($isDuplicate) {
                                        $duplicities[] = $comparedIntegrationObject;
                                    }
                                }
                            } while (next($integrationObjects) !== false);
                            if (count($duplicities) === 0) {

                            } elseif (count($duplicities) === 1) {
                                $duplicate = reset($duplicities);
                                $this->integrationMapper->addObjectIdMapping(); // TODO save mapping
                                $objectMappingDAO->mapIds($comparedInternalObject->getObjectId(), $duplicate->getObjectId());
                                $this->orderObjectsSync($objectMappingDAO, $comparedInternalObject, $duplicate);
                            } else {
                                // TODO more duplicities - log problem
                            }
                        } else {
                            // TODO Add internal object to integration sync order
                        }
                    }
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
                    // Object is new in integration and not duplicate
                    // TODO Add integration object to internal sync order
                }
            }
        }

        $internalOrderResult    = $this->internalSyncDataExchange->executeSyncOrder($this->internalSyncOrder);
        $integrationOrderResult = $this->integrationSyncDataExchange->executeSyncOrder($this->integrationSyncOrder);
        // TODO both parties should provide newly created objects to finish pairing (add temporary ids and pair using them?)

        /**
         * TODO This code try to pull and push everything to all mapped places (do we want that?)
         */
    }

    /**
     * @param ObjectMappingDAO $objectMappingDAO
     * @param ReportObjectDAO  $internalObjectDAO
     * @param ReportObjectDAO  $integrationObjectDAO
     */
    private function orderObjectsSync(ObjectMappingDAO $objectMappingDAO, ReportObjectDAO $internalObjectDAO, ReportObjectDAO $integrationObjectDAO)
    {
        $internalObjectChange    = new ObjectChangeDAO($objectMappingDAO->getInternalObjectName(), $internalObjectDAO->getObjectId());
        $integrationObjectChange = new ObjectChangeDAO($objectMappingDAO->getIntegrationObjectName(), $integrationObjectDAO->getObjectId());

        /** @var FieldMappingDAO[] $fieldMappings */
        $fieldMappings = $objectMappingDAO->getFieldMappings();
        foreach ($fieldMappings as $fieldMappingDAO) {
            $internalInformationChangeRequest    = $this->internalSyncReport->getInformationChangeRequest(
                $objectMappingDAO->getInternalObjectName(),
                $internalObjectDAO->getObjectId(),
                $fieldMappingDAO->getInternalField()
            );
            $integrationInformationChangeRequest = $this->integrationSyncReport->getInformationChangeRequest(
                $objectMappingDAO->getIntegrationObjectName(),
                $integrationObjectDAO->getObjectId(),
                $fieldMappingDAO->getIntegrationField()
            );

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
