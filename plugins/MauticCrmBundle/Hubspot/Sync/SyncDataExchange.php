<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Hubspot\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;

class SyncDataExchange implements SyncDataExchangeInterface
{
    public function __construct(
        private readonly ReportBuilder $reportBuilder,
        private readonly OrderExecutioner $orderExecutioner,
    ) {
    }

    public function getSyncReport(RequestDAO $requestDAO): ReportDAO
    {
        return $this->reportBuilder->build($requestDAO);
    }

    public function executeSyncOrder(OrderDAO $syncOrderDAO): void
    {
        $this->orderExecutioner->execute($syncOrderDAO);
    }
}
