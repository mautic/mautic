<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner;

use Mautic\IntegrationsBundle\Event\InternalObjectCreateEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectUpdateEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectMappingsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\Helper\MappingHelper;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderExecutioner
{
    public function __construct(
        private MappingHelper $mappingHelper,
        private EventDispatcherInterface $dispatcher,
        private ObjectProvider $objectProvider,
        private ReferenceResolverInterface $referenceResolver,
        private FieldValidatorInterface $fieldValidator,
    ) {
    }

    public function execute(OrderDAO $syncOrderDAO): ObjectMappingsDAO
    {
        $identifiedObjects   = $syncOrderDAO->getIdentifiedObjects();
        $unidentifiedObjects = $syncOrderDAO->getUnidentifiedObjects();

        $objectMappings = new ObjectMappingsDAO();

        foreach ($identifiedObjects as $objectName => $updateObjects) {
            $this->referenceResolver->resolveReferences($objectName, $updateObjects);
            $this->fieldValidator->validateFields($objectName, $updateObjects);
            $this->updateObjects($objectMappings, $objectName, $updateObjects, $syncOrderDAO);
        }

        foreach ($unidentifiedObjects as $objectName => $createObjects) {
            $this->referenceResolver->resolveReferences($objectName, $createObjects);
            $this->fieldValidator->validateFields($objectName, $createObjects);
            $this->createObjects($objectMappings, $objectName, $createObjects);
        }

        return $objectMappings;
    }

    /**
     * @param ObjectChangeDAO[] $updateObjects
     */
    private function updateObjects(ObjectMappingsDAO $objectMappings, string $objectName, array $updateObjects, OrderDAO $syncOrderDAO): void
    {
        $updateCount = count($updateObjects);
        DebugLogger::log(
            MauticSyncDataExchange::NAME,
            sprintf(
                'Updating %d %s object(s)',
                $updateCount,
                $objectName
            ),
            self::class.':'.__FUNCTION__
        );

        if (0 === $updateCount) {
            return;
        }

        try {
            $event = new InternalObjectUpdateEvent(
                $this->objectProvider->getObjectByName($objectName),
                $syncOrderDAO->getIdentifiedObjectIds($objectName),
                $updateObjects
            );
        } catch (ObjectNotFoundException) {
            DebugLogger::log(
                MauticSyncDataExchange::NAME,
                $objectName,
                self::class.':'.__FUNCTION__
            );

            return;
        }

        $this->dispatcher->dispatch($event, IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS);
        $updatedObjectMappings = $event->getUpdatedObjectMappings();
        $this->mappingHelper->updateObjectMappings($updatedObjectMappings);

        // The ObjectMapping entity is pushed into UpdatedObjectMappingDAO in MappingHelper::updateObjectMapping in order
        // to make it available to the IntegrationEvents::INTEGRATION_BATCH_SYNC_COMPLETED_* events
        foreach ($updatedObjectMappings as $updatedObjectMapping) {
            if (!$updatedObjectMapping->getObjectMapping()) {
                continue;
            }

            $objectMappings->addUpdatedObjectMapping($updatedObjectMapping->getObjectMapping());
        }
    }

    /**
     * @param ObjectChangeDAO[] $createObjects
     */
    private function createObjects(ObjectMappingsDAO $objectMappings, string $objectName, array $createObjects): void
    {
        $createCount = count($createObjects);

        DebugLogger::log(
            MauticSyncDataExchange::NAME,
            sprintf(
                'Creating %d %s object(s)',
                $createCount,
                $objectName
            ),
            self::class.':'.__FUNCTION__
        );

        if (0 === $createCount) {
            return;
        }

        try {
            $event = new InternalObjectCreateEvent(
                $this->objectProvider->getObjectByName($objectName),
                $createObjects
            );
        } catch (ObjectNotFoundException) {
            DebugLogger::log(
                MauticSyncDataExchange::NAME,
                $objectName,
                self::class.':'.__FUNCTION__
            );

            return;
        }

        $this->dispatcher->dispatch($event, IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS);
        $createdObjectMappings = $event->getObjectMappings();
        $this->mappingHelper->saveObjectMappings($createdObjectMappings);

        // Make ObjectMappings available to the IntegrationEvents::INTEGRATION_BATCH_SYNC_COMPLETED_* events
        foreach ($createdObjectMappings as $createdObjectMapping) {
            $objectMappings->addNewObjectMapping($createdObjectMapping);
        }
    }
}
