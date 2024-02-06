<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncProcess;

use Mautic\IntegrationsBundle\Event\CompletedSyncIterationEvent;
use Mautic\IntegrationsBundle\Event\SyncEvent;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectMappingsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderResultsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\Exception\HandlerNotSupportedException;
use Mautic\IntegrationsBundle\Sync\Helper\MappingHelper;
use Mautic\IntegrationsBundle\Sync\Helper\RelationsHelper;
use Mautic\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\IntegrationsBundle\Sync\Notification\Notifier;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Integration\IntegrationSyncProcess;
use Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\MauticSyncProcess;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SyncProcess
{
    private ?int $syncIteration = null;

    public function __construct(
        private SyncDateHelper $syncDateHelper,
        private MappingHelper $mappingHelper,
        private RelationsHelper $relationsHelper,
        private IntegrationSyncProcess $integrationSyncProcess,
        private MauticSyncProcess $mauticSyncProcess,
        private EventDispatcherInterface $eventDispatcher,
        private Notifier $notifier,
        private MappingManualDAO $mappingManualDAO,
        private MauticSyncDataExchange $internalSyncDataExchange,
        private SyncDataExchangeInterface $integrationSyncDataExchange,
        private InputOptionsDAO $inputOptionsDAO,
        private SyncServiceInterface $syncService
    ) {
    }

    /**
     * Execute sync with integration.
     */
    public function execute(): void
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
            new SyncEvent($this->inputOptionsDAO),
            IntegrationEvents::INTEGRATION_POST_EXECUTE
        );
    }

    private function executeIntegrationSync(): void
    {
        $this->syncIteration = 1;
        while (true) {
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf('Integration to Mautic; syncing iteration %s', $this->syncIteration),
                self::class.':'.__FUNCTION__
            );

            $syncReport = $this->integrationSyncProcess->getSyncReport($this->syncIteration);
            if (!$syncReport->shouldSync()) {
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    'Integration to Mautic; no objects were mapped to be synced',
                    self::class.':'.__FUNCTION__
                );

                break;
            }

            // Update the mappings in case objects have been converted such as Lead -> Contact
            $this->mappingHelper->remapIntegrationObjects($syncReport->getRemappedObjects());

            // Maps relations, synchronizes missing objects if necessary
            $this->manageRelations($syncReport);

            // Convert the integrations' report into an "order" or instructions for Mautic
            $syncOrder = $this->mauticSyncProcess->getSyncOrder($syncReport);
            if (!$syncOrder->shouldSync()) {
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    'Integration to Mautic; no object changes were recorded possible due to field direction configurations',
                    self::class.':'.__FUNCTION__
                );

                break;
            }

            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    'Integration to Mautic; syncing %d total objects',
                    $syncOrder->getObjectCount()
                ),
                self::class.':'.__FUNCTION__
            );

            // Execute the sync instructions
            $objectMappings = $this->internalSyncDataExchange->executeSyncOrder($syncOrder);

            // Dispatch an event to allow subscribers to take action after this batch of objects has been synced to Mautic
            $orderResults = $this->getOrderResultsForIntegrationSync($syncOrder, $objectMappings);
            $this->eventDispatcher->dispatch(
                new CompletedSyncIterationEvent($orderResults, $this->syncIteration, $this->inputOptionsDAO, $this->mappingManualDAO),
                IntegrationEvents::INTEGRATION_BATCH_SYNC_COMPLETED_INTEGRATION_TO_MAUTIC
            );
            unset($orderResults);

            if ($this->shouldStopIntegrationSync()) {
                break;
            }

            // Fetch the next iteration/batch
            ++$this->syncIteration;
        }
    }

    private function executeInternalSync(): void
    {
        $this->syncIteration = 1;
        while (true) {
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf('Mautic to integration; syncing iteration %s', $this->syncIteration),
                self::class.':'.__FUNCTION__
            );

            $syncReport = $this->mauticSyncProcess->getSyncReport($this->syncIteration);

            if (!$syncReport->shouldSync()) {
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    'Mautic to integration; no objects were mapped to be synced',
                    self::class.':'.__FUNCTION__
                );

                break;
            }

            // Convert the internal report into an "order" or instructions for the integration
            $syncOrder = $this->integrationSyncProcess->getSyncOrder($syncReport);

            if (!$syncOrder->shouldSync()) {
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    'Mautic to integration; no object changes were recorded possible due to field direction configurations',
                    self::class.':'.__FUNCTION__
                );

                // Finalize notifications such as injecting user notifications
                $this->notifier->finalizeNotifications();

                break;
            }

            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    'Mautic to integration; syncing %d total objects',
                    $syncOrder->getObjectCount()
                ),
                self::class.':'.__FUNCTION__
            );

            // Execute the sync instructions
            $this->integrationSyncDataExchange->executeSyncOrder($syncOrder);

            // Save mappings and cleanup
            $this->finalizeSync($syncOrder);

            // Dispatch an event to allow subscribers to take action after this batch of objects has been synced to the integration
            $orderResults = $this->getOrderResultsForInternalSync($syncOrder);
            $this->eventDispatcher->dispatch(
                new CompletedSyncIterationEvent($orderResults, $this->syncIteration, $this->inputOptionsDAO, $this->mappingManualDAO),
                IntegrationEvents::INTEGRATION_BATCH_SYNC_COMPLETED_MAUTIC_TO_INTEGRATION
            );
            unset($orderResults);

            // Fetch the next iteration/batch
            ++$this->syncIteration;
        }
    }

    private function manageRelations(ReportDAO $syncReport): void
    {
        // Map relations
        $this->relationsHelper->processRelations($this->mappingManualDAO, $syncReport);

        // Relation objects we need to synchronize
        $objectsToSynchronize = $this->relationsHelper->getObjectsToSynchronize();

        if (!empty($objectsToSynchronize)) {
            $this->synchronizeMissingObjects($objectsToSynchronize, $syncReport);
        }
    }

    private function synchronizeMissingObjects(array $objectsToSynchronize, ReportDAO $syncReport): void
    {
        $inputOptions = $this->getInputOptionsForObjects($objectsToSynchronize);

        // We need to synchronize missing relation ids
        $this->processParallelSync($inputOptions);

        // Now we can map relations for objects we have just synchronised
        $this->relationsHelper->processRelations($this->mappingManualDAO, $syncReport);
    }

    /**
     * @throws \Mautic\IntegrationsBundle\Exception\InvalidValueException
     */
    private function getInputOptionsForObjects(array $objectsToSynchronize): InputOptionsDAO
    {
        $mauticObjectIds = new ObjectIdsDAO();

        foreach ($objectsToSynchronize as $object) {
            $mauticObjectIds->addObjectId($object->getObject(), $object->getObjectId());
        }

        $integration  = $this->mappingManualDAO->getIntegration();

        return new InputOptionsDAO([
            'integration'           => $integration,
            'integration-object-id' => $mauticObjectIds,
        ]);
    }

    /**
     * @throws IntegrationNotFoundException
     */
    private function processParallelSync($inputOptions): void
    {
        $currentSyncProcess = clone $this->integrationSyncProcess;
        $this->syncService->processIntegrationSync($inputOptions);

        // We need to bring back current $inputOptions which were overwritten by new sync
        $this->integrationSyncProcess = $currentSyncProcess;
    }

    private function shouldStopIntegrationSync(): bool
    {
        // We don't want to iterate sync for specific ids
        return null !== $this->inputOptionsDAO->getIntegrationObjectIds();
    }

    /**
     * @throws IntegrationNotFoundException
     * @throws HandlerNotSupportedException
     */
    private function finalizeSync(OrderDAO $syncOrder): void
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

    private function getOrderResultsForIntegrationSync(OrderDAO $syncOrder, ObjectMappingsDAO $objectMappings): OrderResultsDAO
    {
        // New objects were processed by OrderExecutioner
        $newObjectMappings = $objectMappings->getNewMappings();

        // Updated objects were processed by OrderExecutioner
        $updatedObjectMappings = $objectMappings->getUpdatedMappings();

        // Remapped objects
        $remappedObjects = $syncOrder->getRemappedObjects();

        // Deleted objects
        $deletedObjects = $syncOrder->getDeletedObjects();

        return new OrderResultsDAO($newObjectMappings, $updatedObjectMappings, $remappedObjects, $deletedObjects);
    }

    private function getOrderResultsForInternalSync(OrderDAO $syncOrder): OrderResultsDAO
    {
        // New object mappings
        $newObjectMappings = $syncOrder->getObjectMappings();

        // Updated object mappings
        $updatedObjectMappings = [];
        foreach ($syncOrder->getUpdatedObjectMappings() as $updatedObjectMapping) {
            if (!$updatedObjectMapping->getObjectMapping()) {
                continue;
            }

            $updatedObjectMappings[] = $updatedObjectMapping->getObjectMapping();
        }

        // Remapped objects
        $remappedObjects = $syncOrder->getRemappedObjects();

        // Deleted objects
        $deletedObjects = $syncOrder->getDeletedObjects();

        return new OrderResultsDAO($newObjectMappings, $updatedObjectMappings, $remappedObjects, $deletedObjects);
    }
}
