<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services\Sync\IntegrationSyncService;

use MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncDataExchangeService\SyncDataExchangeServiceInterface;

/**
 * Interface IntegrationSyncServiceInterface
 * @package Mautic\PluginBundle\Model\Sync
 */
interface IntegrationSyncServiceInterface
{
    /**
     * @param SyncDataExchangeServiceInterface $syncDataExchangeService
     * @param int $fromTimestamp
     */
    public function processIntegrationSync(SyncDataExchangeServiceInterface $syncDataExchangeService, $fromTimestamp);
}
