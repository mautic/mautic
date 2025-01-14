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
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var array
     */
    private $changes;

    /**
     * LeadChangeEventDispatcher constructor.
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function dispatchEvents(Events\LeadEvent $event, array $changes)
    {
        $this->lead    = $event->getLead();
        $this->changes = $changes;

        $this->dispatchDateIdentifiedEvent($event);
        $this->dispatchPointChangeEvent($event);
        $this->dispatchUtmTagsChangeEvent();
        $this->dispatchDncChangeEvent();
    }

    private function dispatchDateIdentifiedEvent(Events\LeadEvent $event)
    {
        if (!isset($this->changes['dateIdentified'])) {
            return;
        }

        $this->dispatcher->dispatch(LeadEvents::LEAD_IDENTIFIED, $event);
    }

    private function dispatchPointChangeEvent(Events\LeadEvent $event)
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
        $this->dispatcher->dispatch(LeadEvents::LEAD_POINTS_CHANGE, $pointsEvent);
    }

    private function dispatchUtmTagsChangeEvent()
    {
        if (!isset($this->changes['utmtags'])) {
            return;
        }

        $utmTagsEvent = new Events\LeadUtmTagsEvent($this->lead, $this->changes['utmtags']);
        $this->dispatcher->dispatch(LeadEvents::LEAD_UTMTAGS_ADD, $utmTagsEvent);
    }

    private function dispatchDncChangeEvent()
    {
        if (!isset($this->changes['dnc_channel_status'])) {
            return;
        }

        foreach ($this->changes['dnc_channel_status'] as $channel => $status) {
            $oldStatus = isset($status['old_reason']) ? $status['old_reason'] : DoNotContact::IS_CONTACTABLE;
            $newStatus = $status['reason'];

            $event = new Events\ChannelSubscriptionChange($this->lead, $channel, $oldStatus, $newStatus);
            $this->dispatcher->dispatch(LeadEvents::CHANNEL_SUBSCRIPTION_CHANGED, $event);
        }
    }
}
