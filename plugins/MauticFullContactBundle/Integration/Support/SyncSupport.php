<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\Interfaces\SyncInterface;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration;
use MauticPlugin\MauticFullContactBundle\Sync\Mapping\Manual\MappingManualFactory;

class SyncSupport extends FullContactIntegration implements SyncInterface
{
    /**
     * @var MappingManualFactory
     */
    private $mappingManualFactory;

    /**
     * @var SyncDataExchangeInterface
     */
    private $syncDataExchange;

    public function __construct(MappingManualFactory $mappingManualFactory)
    {
        $this->mappingManualFactory = $mappingManualFactory;
    }

    public function getSyncDataExchange(): SyncDataExchangeInterface
    {
        return $this->syncDataExchange;
    }

    public function getMappingManual(): MappingManualDAO
    {
        return $this->mappingManualFactory->getManual();
    }
}
