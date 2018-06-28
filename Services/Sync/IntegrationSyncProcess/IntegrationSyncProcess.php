<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services\Sync\IntegrationSyncProcess;

use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\FieldChangeDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\IntegrationEntityMappingDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\IntegrationFieldMappingDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\IntegrationMappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\IntegrationMappingManualIteratorDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\ObjectChangeDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\SyncOrderDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\SyncReportDAO;
use MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncDataExchangeService\SyncDataExchangeInterface;
use MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncJudgeService\SyncJudgeServiceInterface;
use MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncJudgeService\SyncJudgeServiceService;

class IntegrationSyncProcess
{
    /**
     * @var SyncJudgeServiceInterface
     */
    private $syncJudgeService;

    /**
     * @var IntegrationMappingManualDAO
     */
    private $integrationMappingManual;

    /**
     * @var IntegrationMappingManualIteratorDAO
     */
    private $integrationMappingManualIterator;

    /**
     * @var SyncDataExchangeInterface
     */
    private $internalSyncDataExchange;

    /**
     * @var SyncReportDAO
     */
    private $internalSyncReport;

    /**
     * @var SyncOrderDAO
     */
    private $internalSyncOrder;

    /**
     * @var SyncDataExchangeInterface
     */
    private $integrationSyncDataExchange;

    /**
     * @var SyncReportDAO
     */
    private $integrationSyncReport;

    /**
     * @var SyncOrderDAO
     */
    private $integrationSyncOrder;

    /**
     * IntegrationSyncProcess constructor.
     * @param int    $fromTimestamp
     * @param SyncJudgeServiceInterface $syncJudgeService
     * @param IntegrationMappingManualDAO $integrationMappingManual
     * @param SyncDataExchangeInterface $internalSyncDataExchange
     * @param SyncDataExchangeInterface $integrationSyncDataExchange
     */
    public function __construct(
        $fromTimestamp,
        SyncJudgeServiceInterface $syncJudgeService,
        IntegrationMappingManualDAO $integrationMappingManual,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncDataExchangeInterface $integrationSyncDataExchange
    )
    {
        $this->syncJudgeService = $syncJudgeService;
        $this->integrationMappingManual = $integrationMappingManual;
        $this->integrationMappingManualIterator = new IntegrationMappingManualIteratorDAO($this->integrationMappingManual);
        $this->internalSyncDataExchange = $internalSyncDataExchange;
        $this->integrationSyncDataExchange = $integrationSyncDataExchange;

        $this->internalSyncReport = $this->internalSyncDataExchange->getSyncReport($this->integrationMappingManual, $fromTimestamp);
        $this->integrationSyncReport = $this->integrationSyncDataExchange->getSyncReport($this->integrationMappingManual, $fromTimestamp);

        $this->internalSyncOrder = new SyncOrderDAO();
        $this->integrationSyncOrder = new SyncOrderDAO();
    }

    /**
     * Execute sync with integration
     */
    public function execute()
    {
        $syncTimestamp = 0;
        while (null !== ($currentEntityMapping = $this->integrationMappingManualIterator->getCurrentEntityMapping())) {
            $this->processIntegrationEntitySync($currentEntityMapping, $syncTimestamp);
        }
        $this->internalSyncDataExchange->executeSyncOrder($this->internalSyncOrder);
        $this->integrationSyncDataExchange->executeSyncOrder($this->integrationSyncOrder);
    }

    /**
     * @param IntegrationEntityMappingDAO $currentEntityMapping
     * @param $syncTimestamp
     */
    private function processIntegrationEntitySync(
        IntegrationEntityMappingDAO $currentEntityMapping,
        $syncTimestamp
    )
    {
        $internalEntity = $currentEntityMapping->getInternalEntity();
        $internalEntityId = $currentEntityMapping->getInternalEntityId();
        $integrationEntity = $currentEntityMapping->getIntegrationEntity();
        $integrationEntityId = $currentEntityMapping->getIntegrationEntityId();
        $internalObjectChange = new ObjectChangeDAO($internalEntityId, $internalEntity, $syncTimestamp);
        $integrationObjectChange = new ObjectChangeDAO($integrationEntityId, $integrationEntity, $syncTimestamp);
        $this->integrationMappingManualIterator->resetFieldMapping($currentEntityMapping->getInternalEntity(), true);
        while(null !== ($currentFieldMapping = $this->integrationMappingManualIterator->getCurrentFieldMapping())) {
            $this->processIntegrationFieldSync(
                $currentEntityMapping,
                $currentFieldMapping,
                $internalObjectChange,
                $integrationObjectChange
            );
        }
        $this->internalSyncOrder->addObjectChange($internalObjectChange);
        $this->integrationSyncOrder->addObjectChange($integrationObjectChange);
    }

    /**
     * @param IntegrationEntityMappingDAO $currentEntityMapping
     * @param IntegrationFieldMappingDAO $currentFieldMapping
     * @param ObjectChangeDAO $internalObjectChange
     * @param ObjectChangeDAO $integrationObjectChange
     */
    private function processIntegrationFieldSync(
        IntegrationEntityMappingDAO $currentEntityMapping,
        IntegrationFieldMappingDAO $currentFieldMapping,
        ObjectChangeDAO $internalObjectChange,
        ObjectChangeDAO $integrationObjectChange
    )
    {
        $internalInformationChangeRequest = $this->internalSyncReport->getInformationChangeRequest(
            $currentEntityMapping->getInternalEntity(),
            $currentEntityMapping->getInternalEntityId(),
            $currentFieldMapping->getInternalField()
        );
        $integrationInformationChangeRequest = $this->integrationSyncReport->getInformationChangeRequest(
            $currentEntityMapping->getIntegrationEntity(),
            $currentEntityMapping->getIntegrationEntityId(),
            $currentFieldMapping->getIntegrationField()
        );
        $judgeModes = [
            SyncJudgeServiceService::PRESUMPTION_OF_INNOCENCE_MODE,
            SyncJudgeServiceService::BEST_EVIDENCE_MODE
        ];
        foreach($judgeModes as $judgeMode) {
            try {
                $result = $this->syncJudgeService->adjudicate(
                    $judgeMode,
                    $internalInformationChangeRequest,
                    $integrationInformationChangeRequest
                );
                $internalFieldChange = new FieldChangeDAO($currentFieldMapping->getInternalField(), $result);
                $internalObjectChange->addFieldChange($internalFieldChange);
                $integrationFieldChange = new FieldChangeDAO($currentFieldMapping->getIntegrationField(), $result);
                $integrationObjectChange->addFieldChange($integrationFieldChange);
                break;
            } catch (\LogicException $ex) {
                continue;
            }
        }
    }
}
