<?php

namespace MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService;

use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order\OrderDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report\ReportDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Request\RequestDAO;


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
     * @param RequestDAO $requestDAO
     *
     * @return ReportDAO
     */
    public function getSyncReport(RequestDAO $requestDAO);

    /**
     * @param OrderDAO $syncOrderDAO
     */
    public function executeSyncOrder(OrderDAO $syncOrderDAO);
}
