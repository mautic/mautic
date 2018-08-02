<?php

namespace MauticPlugin\MauticIntegrationsBundle\Helpers\SyncProcess;

use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService\SyncDataExchangeInterface;
use MauticPlugin\MauticIntegrationsBundle\Helpers\SyncJudge\SyncJudgeInterface;

/**
 * Class SyncProcessFactory
 * @package MauticPlugin\MauticIntegrationsBundle\Helpers\SyncProcess
 */
final class SyncProcessFactory implements SyncProcessFactoryInterface
{
    /**
     * @param $fromTimestamp
     * @param SyncJudgeInterface $syncJudgeService
     * @param MappingManualDAO $integrationMappingManual
     * @param SyncDataExchangeInterface $internalSyncDataExchange
     * @param SyncDataExchangeInterface $integrationSyncDataExchange
     *
     * @return SyncProcess
     */
    public function create(
        $fromTimestamp,
        SyncJudgeInterface $syncJudgeService,
        MappingManualDAO $integrationMappingManual,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncDataExchangeInterface $integrationSyncDataExchange
    )
    {
        return new SyncProcess(
            $fromTimestamp,
            $syncJudgeService,
            $integrationMappingManual,
            $internalSyncDataExchange,
            $integrationSyncDataExchange
        );
    }
}
