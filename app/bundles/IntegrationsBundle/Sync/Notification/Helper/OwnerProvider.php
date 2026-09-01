<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\Notification\Helper;

use Mautic\IntegrationsBundle\Event\InternalObjectOwnerEvent;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OwnerProvider
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly ObjectProvider $objectProvider,
    ) {
    }

    /**
     * @param int[] $objectIds
     *
     * @throws ObjectNotSupportedException
     */
    public function getOwnersForObjectIds(string $objectName, array $objectIds): array
    {
        if ([] === $objectIds) {
            return [];
        }

        try {
            $object = $this->objectProvider->getObjectByName($objectName);
        } catch (ObjectNotFoundException) {
            // Throw this exception for BC.
            throw new ObjectNotSupportedException(MauticSyncDataExchange::NAME, $objectName);
        }

        $event = new InternalObjectOwnerEvent($object, $objectIds);

        $this->dispatcher->dispatch($event);

        return $event->getOwners();
    }
}
