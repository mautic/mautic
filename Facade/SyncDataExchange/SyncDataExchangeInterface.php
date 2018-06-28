<?php

namespace MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService;

use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\IntegrationMappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\SyncOrderDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\SyncReportDAO;


/**
 * Interface SyncDataExchangeInterface
 * @package MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService
 */
interface SyncDataExchangeInterface
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
