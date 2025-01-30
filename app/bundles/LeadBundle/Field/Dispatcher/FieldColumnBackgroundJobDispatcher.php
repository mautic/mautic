<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Dispatcher;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\Field\Event\AddColumnBackgroundEvent;
use Mautic\LeadBundle\Field\Event\UpdateColumnBackgroundEvent;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\Field\Exception\AbortColumnUpdateException;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FieldColumnBackgroundJobDispatcher
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @throws AbortColumnCreateException
     * @throws NoListenerException
     */
    public function dispatchPreAddColumnEvent(LeadField $leadField): void
    {
        $action = LeadEvents::LEAD_FIELD_PRE_ADD_COLUMN_BACKGROUND_JOB;

        if (!$this->dispatcher->hasListeners($action)) {
            throw new NoListenerException('There is no Listener for this event');
        }

        $event = new AddColumnBackgroundEvent($leadField);

        $this->dispatcher->dispatch($event, $action);

        if ($event->isPropagationStopped()) {
            throw new AbortColumnCreateException('Column cannot be created now');
        }
    }

    /**
     * @throws AbortColumnUpdateException
     * @throws NoListenerException
     */
    public function dispatchPreUpdateColumnEvent(LeadField $leadField): void
    {
        $action = LeadEvents::LEAD_FIELD_PRE_UPDATE_COLUMN_BACKGROUND_JOB;

        if (!$this->dispatcher->hasListeners($action)) {
            throw new NoListenerException('There is no Listener for this event');
        }

        $event = new UpdateColumnBackgroundEvent($leadField);

        $this->dispatcher->dispatch($event, $action);

        if ($event->isPropagationStopped()) {
            throw new AbortColumnUpdateException('Column cannot be updated now');
        }
    }
}
