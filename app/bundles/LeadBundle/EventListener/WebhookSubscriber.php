<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\PointsChangeEvent;
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
            LeadEvents::LEAD_POST_SAVE      => ['onLeadNewUpdate', 0],
            LeadEvents::LEAD_POINTS_CHANGE  => ['onLeadPointChange', 0],
            LeadEvents::LEAD_POST_DELETE    => ['onLeadDelete', 0],
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
            'label'       => 'mautic.lead.webhook.event.lead.new',
            'description' => 'mautic.lead.webhook.event.lead.new_desc',
        ];

        // add it to the list
        $event->addEvent(LeadEvents::LEAD_POST_SAVE.'_new', $newLead);

        // checkbox for lead updates
        $updatedLead = [
            'label'       => 'mautic.lead.webhook.event.lead.update',
            'description' => 'mautic.lead.webhook.event.lead.update_desc',
        ];

        // add it to the list
        $event->addEvent(LeadEvents::LEAD_POST_SAVE.'_update', $updatedLead);

        // add a checkbox for points
        $leadPoints = [
            'label'       => 'mautic.lead.webhook.event.lead.points',
            'description' => 'mautic.lead.webhook.event.lead.points_desc',
        ];

        // add the points
        $event->addEvent(LeadEvents::LEAD_POINTS_CHANGE, $leadPoints);

        // lead deleted checkbox label & desc
        $leadDeleted = [
            'label'       => 'mautic.lead.webhook.event.lead.deleted',
            'description' => 'mautic.lead.webhook.event.lead.deleted_desc',
        ];

        // add the deleted checkbox
        $event->addEvent(LeadEvents::LEAD_POST_DELETE, $leadDeleted);
    }

    /**
     * @param LeadEvent $event
     */
    public function onLeadNewUpdate(LeadEvent $event)
    {
        $this->webhookModel->queueWebhooksByType(
            $event->isNew() ? LeadEvents::LEAD_POST_SAVE.'_new' : LeadEvents::LEAD_POST_SAVE.'_update',
            [
                'lead'    => $event->getLead(),
                'contact' => $event->getLead(),
            ],
            [
                'leadDetails',
                'userList',
                'publishDetails',
                'ipAddress',
            ]
        );
    }

    /**
     * @param PointsChangeEvent $event
     */
    public function onLeadPointChange(PointsChangeEvent $event)
    {
        $this->webhookModel->queueWebhooksByType(
            LeadEvents::LEAD_POINTS_CHANGE,
            [
                'lead'    => $event->getLead(),
                'contact' => $event->getLead(),
                'points'  => [
                    'old_points' => $event->getOldPoints(),
                    'new_points' => $event->getNewPoints(),
                ],
            ],
            [
                'leadDetails',
                'userList',
                'publishDetails',
                'ipAddress',
            ]
        );
    }

    /**
     * @param LeadEvent $event
     */
    public function onLeadDelete(LeadEvent $event)
    {
        $lead = $event->getLead();
        $this->webhookModel->queueWebhooksByType(
            LeadEvents::LEAD_POST_DELETE,
            [
                'id'      => $lead->deletedId,
                'lead'    => $lead,
                'contact' => $lead,
            ],
            [
                'leadDetails',
                'userList',
                'publishDetails',
                'ipAddress',
            ]
        );
    }
}
