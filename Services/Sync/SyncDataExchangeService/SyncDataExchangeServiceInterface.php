<?php

namespace MauticPlugin\MauticIntegrationsBundle\Services\Sync\SyncDataExchangeService;

use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\IntegrationMappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\SyncOrderDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\SyncReportDAO;


/**
 * Interface SyncDataExchangeServiceInterface
 * @package Mautic\PluginBundle\Model\Sync
 */
interface SyncDataExchangeServiceInterface
{
    /**
     * @return string
     */
    public function getIntegration();

    /**
     * @param IntegrationMappingManualDAO $integrationMapping
     * @param int|null $fromTimestamp
     *
     * @return SyncReportDAO
     */
    public function getSyncReport(IntegrationMappingManualDAO $integrationMapping, $fromTimestamp = null);

    /**
     * @param SyncOrderDAO $syncOrderDAO
     */
    public function executeSyncOrder(SyncOrderDAO $syncOrderDAO);
}
