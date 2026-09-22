<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Dispatcher;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class FieldSaveDispatcher
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @throws NoListenerException
     */
    public function dispatchPreSaveEvent(LeadField $entity, bool $isNew): LeadFieldEvent
    {
        return $this->dispatchEvent(LeadEvents::FIELD_PRE_SAVE, $entity, $isNew);
    }

    /**
     * @throws NoListenerException
     */
    public function dispatchPostSaveEvent(LeadField $entity, bool $isNew): LeadFieldEvent
    {
        return $this->dispatchEvent(LeadEvents::FIELD_POST_SAVE, $entity, $isNew);
    }

    /**
     * @throws NoListenerException
     */
    public function dispatchEvent(string $action, LeadField $entity, bool $isNew, ?LeadFieldEvent $event = null): LeadFieldEvent
    {
        if (!$this->dispatcher->hasListeners($action)) {
            throw new NoListenerException('There is no Listener for '.$action.' event');
        }

        $event ??= new LeadFieldEvent($entity, $isNew);

        $this->dispatcher->dispatch($event, $action);

        return $event;
    }
}
