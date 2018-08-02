<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services\SyncService;

use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService\SyncDataExchangeInterface;

/**
 * Interface SyncServiceInterface
 */
interface SyncServiceInterface
{
    /**
     * @param SyncDataExchangeInterface $syncDataExchangeService
     * @param MappingManualDAO          $integrationMappingManual
     * @param                           $fromTimestamp
     *
     * @return mixed
     */
    public function processIntegrationSync(
        SyncDataExchangeInterface $syncDataExchangeService,
        MappingManualDAO $integrationMappingManual,
        $fromTimestamp
    );
}
