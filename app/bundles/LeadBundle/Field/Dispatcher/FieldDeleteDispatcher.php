<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Dispatcher;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\FieldPostDeleteEvent;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Exception\NoListenerException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class FieldDeleteDispatcher
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @throws NoListenerException
     */
    public function dispatchPostDeleteEvent(LeadField $entity): LeadFieldEvent
    {
        return $this->dispatchEvent(new FieldPostDeleteEvent($entity));
    }

    /**
     * @throws NoListenerException
     */
    private function dispatchEvent(LeadFieldEvent $event): LeadFieldEvent
    {
        if (!$this->dispatcher->hasListeners($event::class)) {
            throw new NoListenerException('There is no Listener for this event');
        }

        $this->dispatcher->dispatch($event);

        return $event;
    }
}
