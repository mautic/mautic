<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Event\ChannelSubscriptionChange;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadChangeCompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\PointsChangeEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\WebhookBundle\Event\WebhookBuilderEvent;
use Mautic\WebhookBundle\Model\WebhookModel;
use Mautic\WebhookBundle\WebhookEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WebhookSubscriber implements EventSubscriberInterface
{
    /**
     * @var WebhookModel
     */
    private $webhookModel;

    public function __construct(WebhookModel $webhookModel)
    {
        $this->webhookModel = $webhookModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            WebhookEvents::WEBHOOK_ON_BUILD           => ['onWebhookBuild', 0],
            LeadEvents::LEAD_POST_SAVE                => ['onLeadNewUpdate', 0],
            LeadEvents::LEAD_POINTS_CHANGE            => ['onLeadPointChange', 0],
            LeadEvents::LEAD_POST_DELETE              => ['onLeadDelete', 0],
            LeadEvents::CHANNEL_SUBSCRIPTION_CHANGED  => ['onChannelSubscriptionChange', 0],
            LeadEvents::LEAD_COMPANY_CHANGE           => ['onLeadCompanyChange', 0],
            LeadEvents::COMPANY_POST_SAVE             => ['onCompanySave', 0],
            LeadEvents::COMPANY_POST_DELETE           => ['onCompanyDelete', 0],
        ];
    }

    /**
     * Add event triggers and actions.
     */
    public function onWebhookBuild(WebhookBuilderEvent $event)
    {
        // add checkbox to the webhook form for new leads
        $event->addEvent(
            LeadEvents::LEAD_POST_SAVE.'_new',
            [
                'label'       => 'mautic.lead.webhook.event.lead.new',
                'description' => 'mautic.lead.webhook.event.lead.new_desc',
            ]
        );

        // checkbox for lead updates
        $event->addEvent(
            LeadEvents::LEAD_POST_SAVE.'_update',
            [
                'label'       => 'mautic.lead.webhook.event.lead.update',
                'description' => 'mautic.lead.webhook.event.lead.update_desc',
            ]
        );

        // add a checkbox for points
        $event->addEvent(
            LeadEvents::LEAD_POINTS_CHANGE,
            [
                'label'       => 'mautic.lead.webhook.event.lead.points',
                'description' => 'mautic.lead.webhook.event.lead.points_desc',
            ]
        );

        // lead deleted checkbox label & desc
        $event->addEvent(
            LeadEvents::LEAD_POST_DELETE,
            [
                'label'       => 'mautic.lead.webhook.event.lead.deleted',
                'description' => 'mautic.lead.webhook.event.lead.deleted_desc',
            ]
        );

        // add a checkbox for do not contact changes
        $event->addEvent(
            LeadEvents::CHANNEL_SUBSCRIPTION_CHANGED,
            [
                'label'       => 'mautic.lead.webhook.event.lead.dnc',
                'description' => 'mautic.lead.webhook.event.lead.dnc_desc',
            ]
        );

        // add checkbox to the webhook form for new/updated companies
        $event->addEvent(
            LeadEvents::LEAD_COMPANY_CHANGE,
            [
                'label'       => 'mautic.lead.webhook.event.lead.company.change',
                'description' => 'mautic.lead.webhook.event.lead.company.change.desc',
            ]
        );

        // add checkbox to the webhook form for new/updated companies
        $event->addEvent(
            LeadEvents::COMPANY_POST_SAVE,
            [
                'label'       => 'mautic.lead.webhook.event.company.new_or_update',
                'description' => 'mautic.lead.webhook.event.company.new_or_update_desc',
            ]
        );

        // add checkbox to the webhook form for deleted companies
        $event->addEvent(
            LeadEvents::COMPANY_POST_DELETE,
            [
                'label'       => 'mautic.lead.webhook.event.company.deleted',
                'description' => 'mautic.lead.webhook.event.company.deleted_desc',
            ]
        );
    }

    public function onLeadNewUpdate(LeadEvent $event)
    {
        $lead = $event->getLead();
        if ($lead->isAnonymous()) {
            // Ignore this contact
            return;
        }

        $changes = $lead->getChanges(true);
        $this->webhookModel->queueWebhooksByType(
        // Consider this a new contact if it was just identified, otherwise consider it updated
            !empty($changes['dateIdentified']) ? LeadEvents::LEAD_POST_SAVE.'_new' : LeadEvents::LEAD_POST_SAVE.'_update',
            [
                'contact' => $event->getLead(),
            ],
            [
                'leadDetails',
                'userList',
                'publishDetails',
                'ipAddress',
                'doNotContactList',
                'tagList',
            ]
        );
    }

    public function onLeadPointChange(PointsChangeEvent $event)
    {
        $this->webhookModel->queueWebhooksByType(
            LeadEvents::LEAD_POINTS_CHANGE,
            [
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
                'doNotContactList',
                'tagList',
            ]
        );
    }

    public function onLeadDelete(LeadEvent $event)
    {
        $lead = $event->getLead();
        $this->webhookModel->queueWebhooksByType(
            LeadEvents::LEAD_POST_DELETE,
            [
                'id'      => $lead->deletedId,
                'contact' => $lead,
            ],
            [
                'leadDetails',
                'userList',
                'publishDetails',
                'doNotContactList',
                'ipAddress',
            ]
        );
    }

    public function onChannelSubscriptionChange(ChannelSubscriptionChange $event)
    {
        $this->webhookModel->queueWebhooksByType(
            LeadEvents::CHANNEL_SUBSCRIPTION_CHANGED,
            [
                'contact'    => $event->getLead(),
                'channel'    => $event->getChannel(),
                'old_status' => $event->getOldStatusVerb(),
                'new_status' => $event->getNewStatusVerb(),
            ],
            [
                'leadDetails',
                'userList',
                'publishDetails',
                'ipAddress',
                'doNotContactList',
                'tagList',
            ]
        );
    }

    public function onLeadCompanyChange(LeadChangeCompanyEvent $event)
    {
        $leads = $event->getLeads();
        if (empty($leads)) {
            $leads = [$event->getLead()];
        }
        foreach ($leads as $lead) {
            $this->webhookModel->queueWebhooksByType(
                LeadEvents::LEAD_COMPANY_CHANGE,
                [
                    'added'    => $event->wasAdded(),
                    'contact'  => $lead,
                    'company'  => $event->getCompany(),
                ],
                [
                ]
            );
        }
    }

    public function onCompanySave(CompanyEvent $event)
    {
        $this->webhookModel->queueWebhooksByType(
            LeadEvents::COMPANY_POST_SAVE,
            [
                'company'    => $event->getCompany(),
            ]
        );
    }

    public function onCompanyDelete(CompanyEvent $event)
    {
        $company = $event->getCompany();
        $this->webhookModel->queueWebhooksByType(
            LeadEvents::COMPANY_POST_DELETE,
            [
                'id'      => $company->deletedId,
                'company' => $event->getCompany(),
            ]
        );
    }
}
