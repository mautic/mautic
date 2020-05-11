<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var ObjectProvider
     */
    private $objectProvider;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        ObjectProvider $objectProvider
    ) {
        $this->dispatcher     = $dispatcher;
        $this->objectProvider = $objectProvider;
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
        } catch (ObjectNotFoundException $e) {
            // Throw this exception for BC.
            throw new ObjectNotSupportedException(MauticSyncDataExchange::NAME, $objectName);
        }

        $event = new InternalObjectOwnerEvent($object, $objectIds);

        $this->dispatcher->dispatch(IntegrationEvents::INTEGRATION_FIND_OWNER_IDS, $event);

        return $event->getOwners();
    }
}
