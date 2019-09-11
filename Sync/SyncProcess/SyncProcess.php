<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncProcess;

use MauticPlugin\IntegrationsBundle\Event\SyncEvent;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\HandlerNotSupportedException;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use MauticPlugin\IntegrationsBundle\Sync\Notification\Notifier;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\Direction\Integration\IntegrationSyncProcess;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\MauticSyncProcess;
use MauticPlugin\IntegrationsBundle\IntegrationEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;

class SyncProcess
{
    /**
     * @var MappingManualDAO
     */
    private $mappingManualDAO;

    /**
     * @var MauticSyncDataExchange
     */
    private $internalSyncDataExchange;

    /**
     * @var SyncDataExchangeInterface
     */
    private $integrationSyncDataExchange;

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
    private $integrationSyncProcess;

    /**
     * @var MauticSyncProcess
     */
    private $mauticSyncProcess;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Notifier
     */
    private $notifier;

    /**
     * @var InputOptionsDAO
     */
    private $inputOptionsDAO;

    /**
     * @var int
     */
    private $syncIteration;

    /**
     * @param SyncDateHelper            $syncDateHelper
     * @param MappingHelper             $mappingHelper
     * @param IntegrationSyncProcess    $integrationSyncProcess
     * @param MauticSyncProcess         $mauticSyncProcess
     * @param EventDispatcherInterface  $eventDispatcher
     * @param Notifier                  $notifier
     * @param MappingManualDAO          $mappingManualDAO
     * @param SyncDataExchangeInterface $internalSyncDataExchange
     * @param SyncDataExchangeInterface $integrationSyncDataExchange
     * @param InputOptionsDAO           $inputOptionsDAO
     */
    public function __construct(
        SyncDateHelper $syncDateHelper,
        MappingHelper $mappingHelper,
        IntegrationSyncProcess $integrationSyncProcess,
        MauticSyncProcess $mauticSyncProcess,
        EventDispatcherInterface $eventDispatcher,
        Notifier $notifier,
        MappingManualDAO $mappingManualDAO,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncDataExchangeInterface $integrationSyncDataExchange,
        InputOptionsDAO $inputOptionsDAO
    ) {
        $this->syncDateHelper              = $syncDateHelper;
        $this->mappingHelper               = $mappingHelper;
        $this->integrationSyncProcess      = $integrationSyncProcess;
        $this->mauticSyncProcess           = $mauticSyncProcess;
        $this->eventDispatcher             = $eventDispatcher;
        $this->notifier                    = $notifier;
        $this->mappingManualDAO            = $mappingManualDAO;
        $this->internalSyncDataExchange    = $internalSyncDataExchange;
        $this->integrationSyncDataExchange = $integrationSyncDataExchange;
        $this->inputOptionsDAO             = $inputOptionsDAO;
    }

    /**
     * Execute sync with integration
     */
    public function execute()
    {
        defined('MAUTIC_INTEGRATION_ACTIVE_SYNC') or define('MAUTIC_INTEGRATION_ACTIVE_SYNC', 1);

        // Setup/prepare for the sync
        $this->syncDateHelper->setSyncDateTimes($this->inputOptionsDAO->getStartDateTime(), $this->inputOptionsDAO->getEndDateTime());
        $this->integrationSyncProcess->setupSync($this->inputOptionsDAO, $this->mappingManualDAO, $this->integrationSyncDataExchange);
        $this->mauticSyncProcess->setupSync($this->inputOptionsDAO, $this->mappingManualDAO, $this->internalSyncDataExchange);

        if ($this->inputOptionsDAO->pullIsEnabled()) {
            $this->executeIntegrationSync();
        }
        
        if ($this->inputOptionsDAO->pushIsEnabled()) {
            $this->executeInternalSync();
        }

        // Tell listeners sync is done
        $this->eventDispatcher->dispatch(
            IntegrationEvents::INTEGRATION_POST_EXECUTE,
            new SyncEvent($this->mappingManualDAO->getIntegration(), $this->inputOptionsDAO->getStartDateTime(), $this->inputOptionsDAO->getEndDateTime())
        );
    }

    private function executeIntegrationSync()
    {
        $this->syncIteration = 1;
        do {
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf("Integration to Mautic; syncing iteration %s", $this->syncIteration),
                __CLASS__.':'.__FUNCTION__
            );

            $syncReport = $this->integrationSyncProcess->getSyncReport($this->syncIteration);
            if (!$syncReport->shouldSync()) {
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    "Integration to Mautic; no objects were mapped to be synced",
                    __CLASS__.':'.__FUNCTION__
                );
                break;
            }

            // Update the mappings in case objects have been converted such as Lead -> Contact
            $this->mappingHelper->remapIntegrationObjects($syncReport->getRemappedObjects());

            // Convert the integrations' report into an "order" or instructions for Mautic
            $syncOrder = $this->mauticSyncProcess->getSyncOrder($syncReport, $this->inputOptionsDAO->isFirstTimeSync(), $this->mappingManualDAO);
            if (!$syncOrder->shouldSync()) {
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    "Integration to Mautic; no object changes were recorded possible due to field direction configurations",
                    __CLASS__.':'.__FUNCTION__
                );

                break;
            }

            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Integration to Mautic; syncing %d total objects",
                    $syncOrder->getObjectCount()
                ),
                __CLASS__.':'.__FUNCTION__
            );

            // Execute the sync instructions
            $this->internalSyncDataExchange->executeSyncOrder($syncOrder);

            // Fetch the next iteration/batch
            ++$this->syncIteration;
        } while (true);
    }

    private function executeInternalSync()
    {
        $this->syncIteration = 1;
        do {
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf("Mautic to integration; syncing iteration %s", $this->syncIteration),
                __CLASS__.':'.__FUNCTION__
            );

            $syncReport = $this->mauticSyncProcess->getSyncReport($this->syncIteration, $this->inputOptionsDAO);

            if (!$syncReport->shouldSync()) {
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    "Mautic to integration; no objects were mapped to be synced",
                    __CLASS__.':'.__FUNCTION__
                );
                break;
            }

            // Convert the internal report into an "order" or instructions for the integration
            $syncOrder = $this->integrationSyncProcess->getSyncOrder($syncReport, $this->inputOptionsDAO->isFirstTimeSync(), $this->mappingManualDAO);

            if (!$syncOrder->shouldSync()) {
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    "Mautic to integration; no object changes were recorded possible due to field direction configurations",
                    __CLASS__.':'.__FUNCTION__
                );

                // Finalize notifications such as injecting user notifications
                $this->notifier->finalizeNotifications();

                break;
            }

            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Mautic to integration; syncing %d total objects",
                    $syncOrder->getObjectCount()
                ),
                __CLASS__.':'.__FUNCTION__
            );

            // Execute the sync instructions
            $this->integrationSyncDataExchange->executeSyncOrder($syncOrder);

            // Save mappings and cleanup
            $this->finalizeSync($syncOrder);

            // Fetch the next iteration/batch
            ++$this->syncIteration;
        } while (true);
    }

    /**
     * @param OrderDAO $syncOrder
     *
     * @throws IntegrationNotFoundException
     * @throws HandlerNotSupportedException
     */
    private function finalizeSync(OrderDAO $syncOrder)
    {
        // Save the mappings between Mautic objects and the integration's objects
        $this->mappingHelper->saveObjectMappings($syncOrder->getObjectMappings());

        // Remap integration objects to Mautic objects if applicable
        $this->mappingHelper->remapIntegrationObjects($syncOrder->getRemappedObjects());

        // Update last sync dates on existing object mappings
        $this->mappingHelper->updateObjectMappings($syncOrder->getUpdatedObjectMappings());

        // Tell sync that these objects have been deleted and not to continue re-syncing them
        $this->mappingHelper->markAsDeleted($syncOrder->getDeletedObjects());

        // Inject notifications
        $this->notifier->noteMauticSyncIssue($syncOrder->getNotifications());

        // Cleanup field tracking for successfully synced objects
        $this->internalSyncDataExchange->cleanupProcessedObjects($syncOrder->getSuccessfullySyncedObjects());
    }
}
