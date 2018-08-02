<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services\SyncService;

use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService\SyncDataExchangeInterface;
use MauticPlugin\MauticIntegrationsBundle\Helpers\SyncJudge\SyncJudgeInterface;
use MauticPlugin\MauticIntegrationsBundle\Helpers\SyncProcess\SyncProcessFactoryInterface;

/**
 * Class SyncService
 * @package MauticPlugin\MauticIntegrationsBundle\Services\Sync\IntegrationSyncService
 */
final class SyncService implements SyncServiceInterface
{
    /**
     * @var SyncProcessFactoryInterface
     */
    private $integrationSyncProcessFactory;

    /**
     * @var SyncJudgeInterface
     */
    private $syncJudgeService;

    /**
     * @var SyncDataExchangeInterface
     */
    private $internalSyncDataExchange;

    /**
     * SyncService constructor.
     *
     * @param SyncProcessFactoryInterface $integrationSyncProcessFactory
     * @param SyncJudgeInterface          $syncJudgeService
     */
    public function __construct(
        SyncProcessFactoryInterface $integrationSyncProcessFactory,
        SyncJudgeInterface $syncJudgeService
    ) {
        $this->integrationSyncProcessFactory = $integrationSyncProcessFactory;
        $this->syncJudgeService              = $syncJudgeService;
    }

    /**
     * @param SyncDataExchangeInterface $syncDataExchangeService
     * @param MappingManualDAO          $integrationMappingManual
     * @param                           $fromTimestamp
     *
     * @return mixed|void
     */
    public function processIntegrationSync(SyncDataExchangeInterface $syncDataExchangeService, MappingManualDAO $integrationMappingManual, $fromTimestamp)
    {
        $integrationSyncProcess   = $this->integrationSyncProcessFactory->create(
            $fromTimestamp,
            $this->syncJudgeService,
            $integrationMappingManual,
            $this->internalSyncDataExchange,
            $syncDataExchangeService
        );
        $integrationSyncProcess->execute();
    }
}
