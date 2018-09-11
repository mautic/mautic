<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncService;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\Mapping\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\SyncDate\SyncDateHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\SyncProcessFactoryInterface;

/**
 * Class SyncService
 */
final class SyncService implements SyncServiceInterface
{
    /**
     * @var SyncJudgeInterface
     */
    private $syncJudge;

    /**
     * @var SyncProcessFactoryInterface
     */
    private $integrationSyncProcessFactory;

    /**
     * @var SyncDateHelper
     */
    private $syncDateHelper;

    /**
     * @var SyncDataExchangeInterface
     */
    private $internalSyncDataExchange;

    /**
     * @var MappingHelper
     */
    private $mappingHelper;

    /**
     * SyncService constructor.
     *
     * @param SyncJudgeInterface          $syncJudge
     * @param SyncProcessFactoryInterface $integrationSyncProcessFactory
     * @param SyncDateHelper              $syncDateHelper
     * @param MauticSyncDataExchange      $internalSyncDataExchange
     * @param MappingHelper               $mappingHelper
     */
    public function __construct(
        SyncJudgeInterface $syncJudge,
        SyncProcessFactoryInterface $integrationSyncProcessFactory,
        SyncDateHelper $syncDateHelper,
        MauticSyncDataExchange $internalSyncDataExchange,
        MappingHelper $mappingHelper
    ) {
        $this->syncJudge                     = $syncJudge;
        $this->integrationSyncProcessFactory = $integrationSyncProcessFactory;
        $this->syncDateHelper                = $syncDateHelper;
        $this->internalSyncDataExchange      = $internalSyncDataExchange;
        $this->mappingHelper                 = $mappingHelper;
    }

    /**
     * @param SyncDataExchangeInterface $syncDataExchangeService
     * @param MappingManualDAO          $integrationMappingManual
     * @param bool                      $firstTimeSync
     * @param \DateTimeInterface|null   $syncFromDateTime
     * @param \DateTimeInterface|null   $syncToDateTime
     */
    public function processIntegrationSync(
        SyncDataExchangeInterface $syncDataExchangeService,
        MappingManualDAO $integrationMappingManual,
        $firstTimeSync,
        \DateTimeInterface $syncFromDateTime = null,
        \DateTimeInterface $syncToDateTime = null
    ) {
        $integrationSyncProcess = $this->integrationSyncProcessFactory->create(
            $this->syncJudge,
            $integrationMappingManual,
            $this->internalSyncDataExchange,
            $syncDataExchangeService,
            $this->syncDateHelper,
            $this->mappingHelper,
            $firstTimeSync,
            $syncFromDateTime,
            $syncToDateTime
        );

        DebugLogger::log(
            $integrationMappingManual->getIntegration(),
            sprintf(
                "Starting %s sync from %s date/time",
                ($firstTimeSync) ? "first time" : "subsequent",
                ($syncFromDateTime) ? $syncFromDateTime->format('Y-m-d H:i:s') : "yet to be determined"
            ),
            __CLASS__.':'.__FUNCTION__
        );

        $integrationSyncProcess->execute();
    }

    public function initiateDebugLogger(DebugLogger $logger)
    {
        // Yes it's a hack to prevent from having to pass the logger as a dependency into dozens of classes
        // So not doing anything with the logger, just need Symfony to initiate the service
    }
}
