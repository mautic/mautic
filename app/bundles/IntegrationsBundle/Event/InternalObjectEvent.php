<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class InternalObjectEvent extends Event
{
    /**
     * @var ObjectInterface[]
     */
    private array $objects = [];

    public function addObject(ObjectInterface $object): void
    {
        $this->objects[] = $object;
    }

    /**
     * @return ObjectInterface[]
     */
    public function getObjects(): array
    {
        return $this->objects;
    }
}
