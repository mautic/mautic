<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class InternalObjectCreateEvent extends Event
{
    /**
     * @var ObjectMapping[]
     */
    private array $objectMappings = [];

    public function __construct(
        private readonly ObjectInterface $object,
        /**
         * @var ObjectChangeDAO[]
         */
        private readonly array $createObjects,
    ) {
    }

    public function getObject(): ObjectInterface
    {
        return $this->object;
    }

    /**
     * @return ObjectChangeDAO[]
     */
    public function getCreateObjects(): array
    {
        return $this->createObjects;
    }

    /**
     * @return ObjectMapping[]
     */
    public function getObjectMappings(): array
    {
        return $this->objectMappings;
    }

    /**
     * @param ObjectMapping[] $objectMappings
     */
    public function setObjectMappings(array $objectMappings): void
    {
        $this->objectMappings = $objectMappings;
    }
}
