<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\PointsChangeEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class LeadSubscriber.
 */
class LeadSubscriber extends WebhookSubscriberBase
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE     => ['onLeadNewUpdate', 0],
            LeadEvents::LEAD_POINTS_CHANGE => ['onLeadPointChange', 0],
            LeadEvents::LEAD_POST_DELETE   => ['onLeadDelete', 0],
            //LeadEvents::LEAD_LIST_BATCH_CHANGE  => array('onLeadEvent', 0),
            //LeadEvents::LEAD_POST_MERGE         => array('onLeadEvent', 0),
            //LeadEvents::LEAD_IDENTIFIED         => array('onLeadEvent', 0),
            //LeadEvents::CURRENT_LEAD_CHANGED    => array('onLeadEvent', 0)
        ];
    }

    /*
     * Generic method to execute when a lead does something
     */
    public function onLeadNewUpdate(LeadEvent $event)
    {
        $serializerGroups = ['leadDetails', 'userList', 'publishDetails', 'ipAddress'];

        $entity = $event->getLead();

        $payload = [
            'lead' => $entity,
        ];

        // get the leads
        if ($event->isNew()) {
            // get our new lead webhook events first
            $webhookEvents = $this->getEventWebooksByType(LeadEvents::LEAD_POST_SAVE.'_new');
            $this->webhookModel->QueueWebhooks($webhookEvents, $payload, $serializerGroups, true);
        }

        // now deal with webhooks for the update event
        if (!$event->isNew()) {
            $webhookEvents = $this->getEventWebooksByType(LeadEvents::LEAD_POST_SAVE.'_update');
            $this->webhookModel->QueueWebhooks($webhookEvents, $payload, $serializerGroups, true);
        }
    }

    /*
     * Method to execute when the lead point value changes.abstract
     * Queues the event payload into the webhook queue so it can be processed
     */
    public function onLeadPointChange(PointsChangeEvent $event)
    {
        /** @var \Mautic\LeadBundle\Entity\Lead $lead */
        $lead = $event->getLead();

        $serializerGroups = ['leadDetails', 'userList', 'publishDetails', 'ipAddress'];

        $payload = [
            'lead'   => $lead,
            'points' => [
                'old_points' => $event->getOldPoints(),
                'new_points' => $event->getNewPoints(),
            ],
        ];

        $types    = [LeadEvents::LEAD_POINTS_CHANGE];
        $webhooks = $this->getEventWebooksByType($types);
        $this->webhookModel->QueueWebhooks($webhooks, $payload, $serializerGroups, true);
    }

    /*
     * Delete lead event
     */
    public function onLeadDelete(LeadEvent $event)
    {
        /** @var \Mautic\LeadBundle\Entity\Lead $lead */
        $lead = $event->getLead();

        $serializerGroups = ['leadDetails', 'userList', 'publishDetails', 'ipAddress'];

        $payload = [
            'lead' => $lead,
        ];

        $types    = [LeadEvents::LEAD_POST_DELETE];
        $webhooks = $this->getEventWebooksByType($types);
        $this->webhookModel->QueueWebhooks($webhooks, $payload, $serializerGroups, true);
    }
}
