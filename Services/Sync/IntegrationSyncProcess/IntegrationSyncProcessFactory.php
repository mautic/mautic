<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services\Sync\IntegrationSyncProcess;

use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\IntegrationMappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncDataExchangeService\SyncDataExchangeServiceInterface;
use MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncJudgeService\SyncJudgeServiceInterface;

/**
 * Class IntegrationSyncProcessFactory
 * @package MauticPlugin\MauticIntegrationsBundle\Services\Sync\IntegrationSyncProcess
 */
final class IntegrationSyncProcessFactory implements IntegrationSyncProcessFactoryInterface
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
    )
    {
        return new IntegrationSyncProcess(
            $fromTimestamp,
            $syncJudgeService,
            $integrationMappingManual,
            $internalSyncDataExchange,
            $integrationSyncDataExchange
        );
    }
}
