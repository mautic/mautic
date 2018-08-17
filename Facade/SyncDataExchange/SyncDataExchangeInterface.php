<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Facade\SyncDataExchange;

use MauticPlugin\IntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Request\RequestDAO;


/**
 * Interface SyncDataExchangeInterface
 * @package MauticPlugin\IntegrationsBundle\Facade\SyncDataExchangeService
 */
interface SyncDataExchangeInterface
{
    /**
     * Sync to integration
     *
     * @param RequestDAO $requestDAO
     *
     * @return ReportDAO
     */
    public function getSyncReport(RequestDAO $requestDAO);

    /**
     * Sync from integration
     *
     * @param OrderDAO $syncOrderDAO
     */
    public function executeSyncOrder(OrderDAO $syncOrderDAO);
}
