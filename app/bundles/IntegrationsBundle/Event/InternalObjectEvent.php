<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;

class InternalObjectEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    /**
     * @var array
     */
    private $objects = [];

    /**
     * @return Integration
     */
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
