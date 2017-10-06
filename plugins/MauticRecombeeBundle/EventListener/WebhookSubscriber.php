<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticRecombeeBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\WebhookBundle\Event\WebhookBuilderEvent;
use Mautic\WebhookBundle\EventListener\WebhookModelTrait;
use Mautic\WebhookBundle\WebhookEvents;

/**
 * Class WebhookSubscriber.
 */
class WebhookSubscriber extends CommonSubscriber
{
    use WebhookModelTrait;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            WebhookEvents::WEBHOOK_ON_BUILD => ['onWebhookBuild', 0],
            LeadEvents::LEAD_POST_SAVE      => ['onLeadChange', 0],
        ];
    }

    /**
     * Add event triggers and actions.
     *
     * @param WebhookBuilderEvent $event
     */
    public function onWebhookBuild(WebhookBuilderEvent $event)
    {
        // add checkbox to the webhook form for new leads
        $newLead = [
            'label'       => 'mautic.plugin.recombee.webhook.event.lead.change',
            'description' => 'mautic.plugin.recombee.webhook.event.lead.change_desc',
        ];

        // add it to the list
        $event->addEvent(LeadEvents::LEAD_POST_SAVE.'_change', $newLead);
    }

    /**
     * @param LeadEvent $event
     */
    public function onLeadChange(LeadEvent $event)
    {
        $lead    = $event->getLead();
        $changes = $lead->getChanges(true);
        $this->webhookModel->queueWebhooksByType(
           LeadEvents::LEAD_POST_SAVE.'_change',
            [
                'lead'    => $event->getLead(),
                'changes' => $changes,
            ]

        );
    }
}
