<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Collector;

use Mautic\FormBundle\Collection\FieldCollection;
use Mautic\FormBundle\Event\FieldCollectEvent;
use Mautic\FormBundle\FormEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class FieldCollector implements FieldCollectorInterface
{
    /**
     * @var FieldCollection[]
     */
    private array $fieldCollections = [];

    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function getFields(string $object): FieldCollection
    {
        if (!isset($this->fieldCollections[$object])) {
            $this->collect($object);
        }

        return $this->fieldCollections[$object];
    }

    private function collect(string $object): void
    {
        $event = new FieldCollectEvent($object);
        $this->dispatcher->dispatch($event, FormEvents::ON_FIELD_COLLECT);
        $this->fieldCollections[$object] = $event->getFields();
    }
}
