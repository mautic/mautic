<?php

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event as Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class LeadChangeEventDispatcher
{
    private ?Lead $lead = null;

    private ?array $changes = null;

    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function dispatchEvents(EntityPostSave $event, array $changes): void
    {
        $lead = $event->getEntity();
        if (!$lead instanceof Lead) {
            return;
        }

        $this->lead    = $lead;
        $this->changes = $changes;

        $this->dispatchDateIdentifiedEvent($event->isNew(), $event->getChanges());
        $this->dispatchPointChangeEvent($event->isNew());
        $this->dispatchUtmTagsChangeEvent();
        $this->dispatchDncChangeEvent();
    }

    private function dispatchDateIdentifiedEvent(bool $isNew, array|bool $eventChanges): void
    {
        if (!isset($this->changes['dateIdentified'])) {
            return;
        }

        $this->dispatcher->dispatch(new Events\LeadIdentifiedEvent($this->lead, $isNew, $eventChanges));
    }

    private function dispatchPointChangeEvent(bool $isNew): void
    {
        if (!isset($this->changes['points'])) {
            return;
        }

        if ($this->lead->imported) {
            return;
        }

        if ((int) $this->changes['points'][0] <= 0 && (int) $this->changes['points'][1] <= 0) {
            return;
        }

        if ($isNew) {
            return;
        }

        $pointsEvent = new Events\PointsChangeEvent($this->lead, (int) $this->changes['points'][0], (int) $this->changes['points'][1]);
        $this->dispatcher->dispatch($pointsEvent);
    }

    private function dispatchUtmTagsChangeEvent(): void
    {
        if (!isset($this->changes['utmtags'])) {
            return;
        }

        $utmTagsEvent = new Events\LeadUtmTagsEvent($this->lead, $this->changes['utmtags']);
        $this->dispatcher->dispatch($utmTagsEvent);
    }

    private function dispatchDncChangeEvent(): void
    {
        if (!isset($this->changes['dnc_channel_status'])) {
            return;
        }

        foreach ($this->changes['dnc_channel_status'] as $channel => $status) {
            $oldStatus = $status['old_reason'] ?? DoNotContact::IS_CONTACTABLE;
            $newStatus = $status['reason'];

            $event = new Events\ChannelSubscriptionChange($this->lead, $channel, $oldStatus, $newStatus);
            $this->dispatcher->dispatch($event);
        }
    }
}
