<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;
use Symfony\Contracts\EventDispatcher\Event;

class InternalObjectUpdateEvent extends Event
{
    /**
     * @var UpdatedObjectMappingDAO[]
     */
    private $updatedObjectMappings = [];

    /**
     * @param \Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO[] $updateObjects
     */
    public function __construct(private ObjectInterface $object, private array $identifiedObjectIds, private array $updateObjects)
    {
    }

    public function getObject(): ObjectInterface
    {
        return $this->object;
    }

    public function getIdentifiedObjectIds(): array
    {
        return $this->identifiedObjectIds;
    }

    public function getUpdateObjects(): array
    {
        return $this->updateObjects;
    }

    /**
     * @return UpdatedObjectMappingDAO[]
     */
    public function getUpdatedObjectMappings(): array
    {
        return $this->updatedObjectMappings;
    }

    /**
     * @param UpdatedObjectMappingDAO[] $updatedObjectMappings
     */
    public function setUpdatedObjectMappings(array $updatedObjectMappings): void
    {
        $this->updatedObjectMappings = $updatedObjectMappings;
    }
}
