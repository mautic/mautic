<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Notification\Helper;

use Mautic\IntegrationsBundle\Event\InternalObjectOwnerEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OwnerProvider
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private ObjectProvider $objectProvider
    ) {
    }

    /**
     * @param int[] $objectIds
     *
     * @return ObjectInterface
     *
     * @throws ObjectNotSupportedException
     */
    public function getOwnersForObjectIds(string $objectName, array $objectIds): array
    {
        if (empty($objectIds)) {
            return [];
        }

        try {
            $object = $this->objectProvider->getObjectByName($objectName);
        } catch (ObjectNotFoundException) {
            // Throw this exception for BC.
            throw new ObjectNotSupportedException(MauticSyncDataExchange::NAME, $objectName);
        }

        $event = new InternalObjectOwnerEvent($object, $objectIds);

        $this->dispatcher->dispatch($event, IntegrationEvents::INTEGRATION_FIND_OWNER_IDS);

        return $event->getOwners();
    }
}
