<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services\Sync\IntegrationSyncService;

use MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncDataExchangeService\SyncDataExchangeInterface;

/**
 * Interface IntegrationSyncServiceInterface
 * @package Mautic\PluginBundle\Model\Sync
 */
interface IntegrationSyncServiceInterface
{
    /**
     * @param SyncDataExchangeInterface $syncDataExchangeService
     * @param int $fromTimestamp
     */
    public function processIntegrationSync(SyncDataExchangeInterface $syncDataExchangeService, $fromTimestamp);
}
