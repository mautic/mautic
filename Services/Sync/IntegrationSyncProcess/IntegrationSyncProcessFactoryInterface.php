<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services\Sync\IntegrationSyncProcess;

use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\IntegrationMappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncDataExchangeService\SyncDataExchangeServiceInterface;
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
     * @param SyncDataExchangeServiceInterface $internalSyncDataExchange
     * @param SyncDataExchangeServiceInterface $integrationSyncDataExchange
     *
     * @return IntegrationSyncProcess
     */
    public function create(
        $fromTimestamp,
        SyncJudgeServiceInterface $syncJudgeService,
        IntegrationMappingManualDAO $integrationMappingManual,
        SyncDataExchangeServiceInterface $internalSyncDataExchange,
        SyncDataExchangeServiceInterface $integrationSyncDataExchange
    );
}
