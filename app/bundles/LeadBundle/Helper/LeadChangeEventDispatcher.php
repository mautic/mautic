<?php

namespace Mautic\LeadBundle\Helper;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event as Events;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LeadChangeEventDispatcher
{
    /**
     * @var Lead
     */
    private $lead;

    private ?array $changes = null;

    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {
    }

    public function dispatchEvents(Events\LeadEvent $event, array $changes): void
    {
        $this->lead    = $event->getLead();
        $this->changes = $changes;

        $this->dispatchDateIdentifiedEvent($event);
        $this->dispatchPointChangeEvent($event);
        $this->dispatchUtmTagsChangeEvent();
        $this->dispatchDncChangeEvent();
    }

    private function dispatchDateIdentifiedEvent(Events\LeadEvent $event): void
    {
        if (!isset($this->changes['dateIdentified'])) {
            return;
        }

        $this->dispatcher->dispatch($event, LeadEvents::LEAD_IDENTIFIED);
    }

    private function dispatchPointChangeEvent(Events\LeadEvent $event): void
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

        if ($event->isNew()) {
            return;
        }

        $pointsEvent = new Events\PointsChangeEvent($this->lead, $this->changes['points'][0], $this->changes['points'][1]);
        $this->dispatcher->dispatch($pointsEvent, LeadEvents::LEAD_POINTS_CHANGE);
    }

    private function dispatchUtmTagsChangeEvent(): void
    {
        if (!isset($this->changes['utmtags'])) {
            return;
        }

        $utmTagsEvent = new Events\LeadUtmTagsEvent($this->lead, $this->changes['utmtags']);
        $this->dispatcher->dispatch($utmTagsEvent, LeadEvents::LEAD_UTMTAGS_ADD);
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
            $this->dispatcher->dispatch($event, LeadEvents::CHANNEL_SUBSCRIPTION_CHANGED);
        }
    }
}
