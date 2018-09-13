<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;


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
     * @param string $integrationName
     *
     * @return ReportDAO
     */
    public function getSyncReport(RequestDAO $requestDAO, string $integrationName = '');

    /**
     * Sync from integration
     *
     * @param OrderDAO $syncOrderDAO
     */
    public function executeSyncOrder(OrderDAO $syncOrderDAO);
}
