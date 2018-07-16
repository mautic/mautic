<?php

namespace MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService;

use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order\OrderDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report\ReportDAO;


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
     * @param MappingManualDAO $integrationMapping
     * @param int|null $fromTimestamp
     *
     * @return ReportDAO
     */
    public function getSyncReport(MappingManualDAO $integrationMapping, $fromTimestamp = null);

    /**
     * @param OrderDAO $syncOrderDAO
     */
    public function executeSyncOrder(OrderDAO $syncOrderDAO);
}
