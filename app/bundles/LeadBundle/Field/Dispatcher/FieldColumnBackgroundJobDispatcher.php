<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Dispatcher;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\Field\Event\AddColumnBackgroundEvent;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FieldColumnBackgroundJobDispatcher
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
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

        $this->dispatcher->dispatch($action, $event);

        if ($event->isPropagationStopped()) {
            throw new AbortColumnCreateException('Column cannot be created now');
        }
    }
}
