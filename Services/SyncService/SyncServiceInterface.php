<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services\SyncService;

use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService\SyncDataExchangeInterface;

/**
 * Interface SyncServiceInterface
 * @package MauticPlugin\MauticIntegrationsBundle\Services\Sync\IntegrationSyncService
 */
interface SyncServiceInterface
{
    /**
     * @param SyncDataExchangeInterface $syncDataExchangeService
     * @param int $fromTimestamp
     */
    public function processIntegrationSync(SyncDataExchangeInterface $syncDataExchangeService, $fromTimestamp);
}
