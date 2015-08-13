<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use JMS\Serializer\Serializer;
use Mautic\WebhookBundle\EventListener\WebhookSubscriberBase;
use Mautic\CoreBundle\Factory\MauticFactory;
use Doctrine\ORM\NoResultException;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\PointsChangeEvent;
use Mautic\ApiBundle\Serializer\Exclusion\PublishDetailsExclusionStrategy;


/**
 * Class LeadSubscriber
 *
 * @package Mautic\Webhook\EventListener
 */
class LeadSubscriber extends WebhookSubscriberBase
{
    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            LeadEvents::LEAD_POST_SAVE            => array('onLeadNewUpdate', 0),
            LeadEvents::LEAD_POINTS_CHANGE        => array('onLeadPointChange', 0),
            LeadEvents::LEAD_PRE_DELETE           => array('onLeadDelete', 0),
            //LeadEvents::LEAD_LIST_BATCH_CHANGE  => array('onLeadEvent', 0),
            //LeadEvents::LEAD_POST_MERGE         => array('onLeadEvent', 0),
            //LeadEvents::LEAD_IDENTIFIED         => array('onLeadEvent', 0),
            //LeadEvents::CURRENT_LEAD_CHANGED    => array('onLeadEvent', 0)
        );
    }

    /*
     * Generic method to execute when a lead does something
     */
    public function onLeadNewUpdate(LeadEvent $event)
    {
        $serializerGroups = array("leadDetails", "userList", "publishDetails", "ipAddress");

        $entity = $event->getLead();

        // get the leads
        if ($event->isNew()) {
            // get our new lead webhook events first
            $webhookEvents = $this->getEventWebooksByType(LeadEvents::LEAD_POST_SAVE . '.new');
            $this->webhookModel->QueueWebhooks($webhookEvents, $entity, 'lead', $serializerGroups, true);
        }

        // now deal with webhooks for the update event
        if (! $event->isNew()) {
            $webhookEvents = $this->getEventWebooksByType(LeadEvents::LEAD_POST_SAVE . '.update');
            $this->webhookModel->QueueWebhooks($webhookEvents, $entity, 'leads', $serializerGroups, true);
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

        $payload = array(
            'lead' => json_decode($this->serializeData($lead)),
            'points' => array(
                'old_points' => $event->getOldPoints(),
                'new_points' => $event->getNewPoints()
            )
        );

        $types    = array(LeadEvents::LEAD_POINTS_CHANGE);
        $webhooks = $this->getEventWebooksByType($types);
        $this->webhookModel->QueueWebhooks($webhooks, json_encode($payload), true);
    }

    /*
     * Delete lead event
     */
    public function onLeadDelete(LeadEvent $event)
    {
        /** @var \Mautic\LeadBundle\Entity\Lead $lead */
        $lead = $event->getLead();

        $payload  = array('id' => $lead->getId());

        $types    = array(LeadEvents::LEAD_PRE_DELETE);
        $webhooks = $this->getEventWebooksByType($types);
        $this->webhookModel->QueueWebhooks($webhooks, json_encode($payload), true);
    }
}