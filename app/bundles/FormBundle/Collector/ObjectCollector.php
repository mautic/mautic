<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Collector;

use Mautic\FormBundle\Collection\ObjectCollection;
use Mautic\FormBundle\Event\ObjectCollectEvent;
use Mautic\FormBundle\FormEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ObjectCollector implements ObjectCollectorInterface
{
    private EventDispatcherInterface $dispatcher;
    private ?ObjectCollection $objects = null;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function getObjects(): ObjectCollection
    {
        if (null === $this->objects) {
            $this->collect();
        }

        return $this->objects;
    }

    private function collect(): void
    {
        $event = new ObjectCollectEvent();
        $this->dispatcher->dispatch($event, FormEvents::ON_OBJECT_COLLECT);
        $this->objects = $event->getObjects();
    }
}
