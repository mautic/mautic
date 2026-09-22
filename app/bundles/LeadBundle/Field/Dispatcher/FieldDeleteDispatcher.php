<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Dispatcher;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

readonly class FieldDeleteDispatcher
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
        return $this->dispatchEvent(LeadEvents::FIELD_POST_DELETE, $entity);
    }

    /**
     * @param string $action - Use constant from LeadEvents class (e.g. LeadEvents::FIELD_PRE_SAVE)
     *
     * @throws NoListenerException
     */
    private function dispatchEvent(string $action, LeadField $entity, ?LeadFieldEvent $event = null): LeadFieldEvent
    {
        if (!$this->dispatcher->hasListeners($action)) {
            throw new NoListenerException('There is no Listener for this event');
        }

        $event ??= new LeadFieldEvent($entity);

        $this->dispatcher->dispatch($event, $action);

        return $event;
    }
}
