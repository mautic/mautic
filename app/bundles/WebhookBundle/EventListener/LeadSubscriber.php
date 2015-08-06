<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Mautic\WebhookBundle\EventListener\WebhookSubscriberBase;
use Mautic\CoreBundle\Factory\MauticFactory;
use Doctrine\ORM\NoResultException;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\LeadEvents;

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
            LeadEvents::LEAD_POST_SAVE          => array('onLeadEvent', 0),
            LeadEvents::LEAD_POINTS_CHANGE      => array('onLeadEvent', 0),
            LeadEvents::LEAD_LIST_BATCH_CHANGE  => array('onLeadEvent', 0),
            LeadEvents::LEAD_POST_DELETE        => array('onLeadEvent', 0),
            LeadEvents::LEAD_POST_MERGE         => array('onLeadEvent', 0),
            LeadEvents::LEAD_IDENTIFIED         => array('onLeadEvent', 0),
            LeadEvents::CURRENT_LEAD_CHANGED    => array('onLeadEvent', 0)
        );
    }

    /*
     * Generic method to execute when a lead does something
     */
    public function onLeadEvent($event)
    {
        /** @var \Mautic\LeadBundle\Entity\Lead $lead */
        $lead = ($event->getLead());

        $payload = json_encode($lead->convertToArray());

        $types = array('webhook.lead.new');
        $webhooks = $this->getWebhooksByTypes($types);
        $this->webhookModel->QueueWebhooks($webhooks, $payload, true);
    }
}