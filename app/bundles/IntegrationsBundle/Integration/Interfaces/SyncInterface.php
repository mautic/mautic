<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;

interface SyncInterface extends IntegrationInterface
{
    public function getMappingManual(): MappingManualDAO;

    public function getSyncDataExchange(): SyncDataExchangeInterface;
}
