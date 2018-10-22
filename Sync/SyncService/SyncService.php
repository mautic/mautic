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

use GuzzleHttp\Exception\ClientException;
use MauticPlugin\IntegrationsBundle\Helper\SyncIntegrationsHelper;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\Direction\Integration\IntegrationSyncProcess;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\MauticSyncProcess;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\SyncProcessFactoryInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class SyncService
 */
final class SyncService implements SyncServiceInterface
{
    /**
     * @var SyncProcessFactoryInterface
     */
    private $integrationSyncProcessFactory;

    /**
     * @var SyncDataExchangeInterface
     */
    private $internalSyncDataExchange;

    /**
     * @var SyncDateHelper
     */
    private $syncDateHelper;

    /**
     * @var MappingHelper
     */
    private $mappingHelper;

    /**
     * @var IntegrationSyncProcess
     */
    private $integratinSyncProcess;

    /**
     * @var MauticSyncProcess
     */
    private $mauticSyncProcess;

    /**
     * @var SyncIntegrationsHelper
     */
    private $syncIntegrationsHelper;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * SyncService constructor.
     *
     * @param SyncProcessFactoryInterface $integrationSyncProcessFactory
     * @param MauticSyncDataExchange      $internalSyncDataExchange
     * @param SyncDateHelper              $syncDateHelper
     * @param MappingHelper               $mappingHelper
     * @param SyncIntegrationsHelper      $syncIntegrationsHelper
     * @param EventDispatcherInterface    $eventDispatcher
     * @param IntegrationSyncProcess      $integrationSyncProcess
     * @param MauticSyncProcess           $mauticSyncProcess
     */
    public function __construct(
        SyncProcessFactoryInterface $integrationSyncProcessFactory,
        MauticSyncDataExchange $internalSyncDataExchange,
        SyncDateHelper $syncDateHelper,
        MappingHelper $mappingHelper,
        SyncIntegrationsHelper $syncIntegrationsHelper,
        EventDispatcherInterface $eventDispatcher,
        IntegrationSyncProcess $integrationSyncProcess,
        MauticSyncProcess $mauticSyncProcess
    ) {
        $this->integrationSyncProcessFactory = $integrationSyncProcessFactory;
        $this->internalSyncDataExchange      = $internalSyncDataExchange;
        $this->syncDateHelper                = $syncDateHelper;
        $this->mappingHelper                 = $mappingHelper;
        $this->syncIntegrationsHelper        = $syncIntegrationsHelper;
        $this->eventDispatcher               = $eventDispatcher;
        $this->integratinSyncProcess         = $integrationSyncProcess;
        $this->mauticSyncProcess             = $mauticSyncProcess;
    }

    /**
     * @param string                  $integration
     * @param bool                    $firstTimeSync
     * @param \DateTimeInterface|null $syncFromDateTime
     * @param \DateTimeInterface|null $syncToDateTime
     *
     * @throws \MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException
     */
    public function processIntegrationSync(
        string $integration,
        $firstTimeSync,
        \DateTimeInterface $syncFromDateTime = null,
        \DateTimeInterface $syncToDateTime = null
    )
    {
        $integrationSyncProcess = $this->integrationSyncProcessFactory->create(
            $this->syncDateHelper,
            $this->mappingHelper,
            $this->integratinSyncProcess,
            $this->mauticSyncProcess,
            $this->eventDispatcher,
            $this->internalSyncDataExchange,
            $this->syncIntegrationsHelper->getSyncDataExchange($integration),
            $this->syncIntegrationsHelper->getMappingManual($integration),
            $firstTimeSync,
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

        try {
            $integrationSyncProcess->execute();
        } catch (ClientException $exception) {
            // The sync failed to communicate with the integration so log it
            DebugLogger::log($integration, $exception->getMessage(), null, [], LogLevel::ERROR);
        }
    }

    /**
     * @param DebugLogger $logger
     */
    public function initiateDebugLogger(DebugLogger $logger)
    {
        // Yes it's a hack to prevent from having to pass the logger as a dependency into dozens of classes
        // So not doing anything with the logger, just need Symfony to initiate the service
    }
}
