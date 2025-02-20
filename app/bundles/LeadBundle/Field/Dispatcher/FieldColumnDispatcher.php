<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Dispatcher;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\Event\AddColumnEvent;
use Mautic\LeadBundle\Field\Event\UpdateColumnEvent;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\Field\Exception\AbortColumnUpdateException;
use Mautic\LeadBundle\Field\Settings\BackgroundSettings;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FieldColumnDispatcher
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private BackgroundSettings $backgroundSettings,
    ) {
    }

    /**
     * @throws AbortColumnCreateException
     */
    public function dispatchPreAddColumnEvent(LeadField $leadField): void
    {
        $shouldProcessInBackground = $this->backgroundSettings->shouldProcessColumnChangeInBackground();
        $event                     = new AddColumnEvent($leadField, $shouldProcessInBackground);

        $this->dispatcher->dispatch($event, LeadEvents::LEAD_FIELD_PRE_ADD_COLUMN);

        if ($shouldProcessInBackground) {
            throw new AbortColumnCreateException('Column change will be processed in background job');
        }
    }

    /**
     * @throws AbortColumnUpdateException
     */
    public function dispatchPreUpdateColumnEvent(LeadField $leadField): void
    {
        $shouldProcessInBackground = $this->backgroundSettings->shouldProcessColumnChangeInBackground();
        $event                     = new UpdateColumnEvent($leadField, $shouldProcessInBackground);

        $this->dispatcher->dispatch($event, LeadEvents::LEAD_FIELD_PRE_UPDATE_COLUMN);

        if ($event->shouldProcessInBackground()) {
            throw new AbortColumnUpdateException('Column change will be processed in background job');
        }
    }
}
