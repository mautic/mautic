<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal;

use Mautic\IntegrationsBundle\Event\InternalObjectEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ObjectProvider
{
    /**
     * Cached internal objects.
     *
     * @var ObjectInterface[]
     */
    private array $objects = [];

    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @throws ObjectNotFoundException
     */
    public function getObjectByName(string $name): ObjectInterface
    {
        $this->collectObjects();

        foreach ($this->objects as $object) {
            if ($object->getName() === $name) {
                return $object;
            }
        }

        throw new ObjectNotFoundException("Internal object '{$name}' was not found");
    }

    /**
     * @throws ObjectNotFoundException
     */
    public function getObjectByEntityName(string $entityName): ObjectInterface
    {
        $this->collectObjects();

        foreach ($this->objects as $object) {
            if ($object->getEntityName() === $entityName) {
                return $object;
            }
        }

        throw new ObjectNotFoundException("Internal object was not found for entity '{$entityName}'");
    }

    /**
     * Dispatches an event to collect all internal objects.
     * It caches the objects to a local property so it won't dispatch every time but only once.
     */
    private function collectObjects(): void
    {
        if (empty($this->objects)) {
            $event = new InternalObjectEvent();
            $this->dispatcher->dispatch($event, IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS);
            $this->objects = $event->getObjects();
        }
    }
}
