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

use MauticPlugin\IntegrationsBundle\Helper\SyncIntegrationsHelper;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\SyncProcessFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @var SyncIntegrationsHelper
     */
    private $syncIntegrationsHelper;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * SyncService constructor.
     *
     * @param SyncJudgeInterface          $syncJudge
     * @param SyncProcessFactoryInterface $integrationSyncProcessFactory
     * @param SyncDateHelper              $syncDateHelper
     * @param MauticSyncDataExchange      $internalSyncDataExchange
     * @param MappingHelper               $mappingHelper
     * @param SyncIntegrationsHelper      $syncIntegrationsHelper
     * @param EventDispatcher             $eventDispatcher
     */
    public function __construct(
        SyncJudgeInterface $syncJudge,
        SyncProcessFactoryInterface $integrationSyncProcessFactory,
        SyncDateHelper $syncDateHelper,
        MauticSyncDataExchange $internalSyncDataExchange,
        MappingHelper $mappingHelper,
        SyncIntegrationsHelper $syncIntegrationsHelper,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->syncJudge                     = $syncJudge;
        $this->integrationSyncProcessFactory = $integrationSyncProcessFactory;
        $this->syncDateHelper                = $syncDateHelper;
        $this->internalSyncDataExchange      = $internalSyncDataExchange;
        $this->mappingHelper                 = $mappingHelper;
        $this->syncIntegrationsHelper        = $syncIntegrationsHelper;
        $this->eventDispatcher               = $eventDispatcher;
    }

    /**
     * @param string                  $integration
     * @param bool                    $firstTimeSync
     * @param \DateTimeInterface|null $syncFromDateTime
     * @param \DateTimeInterface|null $syncToDateTime
     *
     * @throws \MauticPlugin\IntegrationsBundle\Sync\Exception\IntegrationNotFoundException
     */
    public function processIntegrationSync(
        string $integration,
        $firstTimeSync,
        \DateTimeInterface $syncFromDateTime = null,
        \DateTimeInterface $syncToDateTime = null
    ) {
        $integrationSyncProcess = $this->integrationSyncProcessFactory->create(
            $this->syncJudge,
            $this->syncIntegrationsHelper->getMappingManual($integration),
            $this->internalSyncDataExchange,
            $this->syncIntegrationsHelper->getSyncDataExchange($integration),
            $this->syncDateHelper,
            $this->mappingHelper,
            $firstTimeSync,
            $this->eventDispatcher,
            $syncFromDateTime,
            $syncToDateTime
        );

        DebugLogger::log(
            $integration,
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
