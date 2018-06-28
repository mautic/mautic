<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services\Sync\IntegrationSyncProcess;

use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\IntegrationMappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncDataExchangeService\SyncDataExchangeInterface;
use MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncJudgeService\SyncJudgeServiceInterface;

/**
 * Interface IntegrationSyncProcessFactoryInterface
 */
interface IntegrationSyncProcessFactoryInterface
{
    /**
     * @param $fromTimestamp
     * @param SyncJudgeServiceInterface $syncJudgeService
     * @param IntegrationMappingManualDAO $integrationMappingManual
     * @param SyncDataExchangeInterface $internalSyncDataExchange
     * @param SyncDataExchangeInterface $integrationSyncDataExchange
     *
     * @return IntegrationSyncProcess
     */
    public function create(
        $fromTimestamp,
        SyncJudgeServiceInterface $syncJudgeService,
        IntegrationMappingManualDAO $integrationMappingManual,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncDataExchangeInterface $integrationSyncDataExchange
    );
}
