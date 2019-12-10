<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner;

use MauticPlugin\IntegrationsBundle\Event\InternalObjectCreateEvent;
use MauticPlugin\IntegrationsBundle\Event\InternalObjectUpdateEvent;
use MauticPlugin\IntegrationsBundle\IntegrationEvents;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderExecutioner
{
    /**
     * @var MappingHelper
     */
    private $mappingHelper;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var ObjectProvider
     */
    private $objectProvider;

    /**
     * @param MappingHelper            $mappingHelper
     * @param EventDispatcherInterface $dispatcher
     * @param ObjectProvider           $objectProvider
     */
    public function __construct(
        MappingHelper $mappingHelper,
        EventDispatcherInterface $dispatcher,
        ObjectProvider $objectProvider
    ) {
        $this->mappingHelper  = $mappingHelper;
        $this->dispatcher     = $dispatcher;
        $this->objectProvider = $objectProvider;
    }

    /**
     * @param OrderDAO $syncOrderDAO
     */
    public function execute(OrderDAO $syncOrderDAO): void
    {
        $identifiedObjects   = $syncOrderDAO->getIdentifiedObjects();
        $unidentifiedObjects = $syncOrderDAO->getUnidentifiedObjects();

        foreach ($identifiedObjects as $objectName => $updateObjects) {
            $this->updateObjects($objectName, $updateObjects, $syncOrderDAO);
        }

        foreach ($unidentifiedObjects as $objectName => $createObjects) {
            $this->createObjects($objectName, $createObjects);
        }
    }

    /**
     * @param string   $objectName
     * @param array    $updateObjects
     * @param OrderDAO $syncOrderDAO
     */
    private function updateObjects(string $objectName, array $updateObjects, OrderDAO $syncOrderDAO): void
    {
        $updateCount = count($updateObjects);
        DebugLogger::log(
            MauticSyncDataExchange::NAME,
            sprintf(
                'Updating %d %s object(s)',
                $updateCount,
                $objectName
            ),
            __CLASS__.':'.__FUNCTION__
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
        } catch (ObjectNotFoundException $e) {
            DebugLogger::log(
                MauticSyncDataExchange::NAME,
                $objectName,
                __CLASS__.':'.__FUNCTION__
            );
        }

        $this->dispatcher->dispatch(IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS, $event);
        $this->mappingHelper->updateObjectMappings($event->getUpdatedObjectMappings());
    }

    /**
     * @param string $objectName
     * @param array  $createObjects
     */
    private function createObjects(string $objectName, array $createObjects): void
    {
        $createCount = count($createObjects);

        DebugLogger::log(
            MauticSyncDataExchange::NAME,
            sprintf(
                'Creating %d %s object(s)',
                $createCount,
                $objectName
            ),
            __CLASS__.':'.__FUNCTION__
        );

        if (0 === $createCount) {
            return;
        }

        try {
            $event = new InternalObjectCreateEvent(
                $this->objectProvider->getObjectByName($objectName),
                $createObjects
            );
        } catch (ObjectNotFoundException $e) {
            DebugLogger::log(
                MauticSyncDataExchange::NAME,
                $objectName,
                __CLASS__.':'.__FUNCTION__
            );
        }

        $this->dispatcher->dispatch(IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS, $event);
        $this->mappingHelper->saveObjectMappings($event->getObjectMappings());
    }
}
