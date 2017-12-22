<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Events\LeadEvent $event
     * @param array            $changes
     */
    public function dispatchEvents(Events\LeadEvent $event, array $changes)
    {
        $this->lead    = $event->getLead();
        $this->changes = $changes;

        $this->dispatchDateIdentifiedEvent($event);
        $this->dispatchPointChangeEvent($event);
        $this->dispatchUtmTagsChangeEvent();
        $this->dispatchDncChangeEvent();
    }

    /**
     * @param Events\LeadEvent $event
     */
    private function dispatchDateIdentifiedEvent(Events\LeadEvent $event)
    {
        if (!isset($this->changes['dateIdentified'])) {
            return;
        }

        $this->dispatcher->dispatch(LeadEvents::LEAD_IDENTIFIED, $event);
    }

    /**
     * @param Events\LeadEvent $event
     */
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
