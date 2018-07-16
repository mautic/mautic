<?php

namespace MauticPlugin\MauticIntegrationsBundle\Helpers\SyncProcess;

use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order\FieldDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\EntityMappingDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\FieldMappingDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\MappingManualIteratorDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order\OrderDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report\ReportDAO;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService\SyncDataExchangeInterface;
use MauticPlugin\MauticIntegrationsBundle\Helpers\SyncJudgeService\SyncJudgeInterface;

/**
 * Class IntegrationSyncProcess
 * @package MauticPlugin\MauticIntegrationsBundle\Helpers\SyncProcess
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
    private $integrationMappingManual;

    /**
     * @var MappingManualIteratorDAO
     */
    private $integrationMappingManualIterator;

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
     * @param int    $fromTimestamp
     * @param SyncJudgeInterface $syncJudgeService
     * @param MappingManualDAO $integrationMappingManual
     * @param SyncDataExchangeInterface $internalSyncDataExchange
     * @param SyncDataExchangeInterface $integrationSyncDataExchange
     */
    public function __construct(
        $fromTimestamp,
        SyncJudgeInterface $syncJudgeService,
        MappingManualDAO $integrationMappingManual,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncDataExchangeInterface $integrationSyncDataExchange
    )
    {
        $this->syncJudgeService = $syncJudgeService;
        $this->integrationMappingManual = $integrationMappingManual;
        $this->integrationMappingManualIterator = new MappingManualIteratorDAO($this->integrationMappingManual);
        $this->internalSyncDataExchange = $internalSyncDataExchange;
        $this->integrationSyncDataExchange = $integrationSyncDataExchange;

        $this->internalSyncReport = $this->internalSyncDataExchange->getSyncReport($this->integrationMappingManual, $fromTimestamp);
        $this->integrationSyncReport = $this->integrationSyncDataExchange->getSyncReport($this->integrationMappingManual, $fromTimestamp);
    }

    /**
     * Execute sync with integration
     */
    public function execute()
    {
        $syncTimestamp = 0;
        $this->internalSyncOrder = new OrderDAO($syncTimestamp);
        $this->integrationSyncOrder = new OrderDAO($syncTimestamp);
        while (null !== ($currentEntityMapping = $this->integrationMappingManualIterator->getCurrentEntityMapping())) {
            $this->processIntegrationEntitySync($currentEntityMapping);
        }
        $this->internalSyncDataExchange->executeSyncOrder($this->internalSyncOrder);
        $this->integrationSyncDataExchange->executeSyncOrder($this->integrationSyncOrder);
    }

    /**
     * @param EntityMappingDAO  $currentEntityMapping
     */
    private function processIntegrationEntitySync(
        EntityMappingDAO $currentEntityMapping
    )
    {
        $internalEntity = $currentEntityMapping->getInternalEntity();
        $internalEntityId = $currentEntityMapping->getInternalEntityId();
        $integrationEntity = $currentEntityMapping->getIntegrationEntity();
        $integrationEntityId = $currentEntityMapping->getIntegrationEntityId();
        $internalObjectChange = new ObjectChangeDAO($internalEntityId, $internalEntity);
        $integrationObjectChange = new ObjectChangeDAO($integrationEntityId, $integrationEntity);
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
     * @param EntityMappingDAO  $currentEntityMapping
     * @param FieldMappingDAO   $currentFieldMapping
     * @param ObjectChangeDAO   $internalObjectChange
     * @param ObjectChangeDAO   $integrationObjectChange
     */
    private function processIntegrationFieldSync(
        EntityMappingDAO $currentEntityMapping,
        FieldMappingDAO $currentFieldMapping,
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
            SyncJudgeInterface::PRESUMPTION_OF_INNOCENCE_MODE,
            SyncJudgeInterface::BEST_EVIDENCE_MODE
        ];
        foreach($judgeModes as $judgeMode) {
            try {
                $result = $this->syncJudgeService->adjudicate(
                    $judgeMode,
                    $internalInformationChangeRequest,
                    $integrationInformationChangeRequest
                );
                $internalFieldChange = new FieldDAO($currentFieldMapping->getInternalField(), $result);
                $internalObjectChange->addField($internalFieldChange);
                $integrationFieldChange = new FieldDAO($currentFieldMapping->getIntegrationField(), $result);
                $integrationObjectChange->addField($integrationFieldChange);
                break;
            } catch (\LogicException $ex) {
                continue;
            }
        }
    }
}
