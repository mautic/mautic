<?php

namespace Mautic\WebhookBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event as Events;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\WebhookBundle\Form\Type\CampaignEventSendWebhookType;
use Mautic\WebhookBundle\Helper\CampaignHelper;
use Mautic\WebhookBundle\WebhookEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CampaignHelper $campaignHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD         => ['onCampaignBuild', 0],
            WebhookEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
        ];
    }

    public function onCampaignTriggerAction(CampaignExecutionEvent $event): void
    {
        if ($event->checkContext('campaign.sendwebhook')) {
            try {
                $this->campaignHelper->fireWebhook($event->getConfig(), $event->getLead());
                $event->setResult(true);
            } catch (\Exception $e) {
                $event->setFailed($e->getMessage());
            }
        }
    }

    /**
     * Add event triggers and actions.
     */
    public function onCampaignBuild(Events\CampaignBuilderEvent $event): void
    {
        $sendWebhookAction = [
            'label'              => 'mautic.webhook.event.sendwebhook',
            'description'        => 'mautic.webhook.event.sendwebhook_desc',
            'formType'           => CampaignEventSendWebhookType::class,
            'formTypeCleanMasks' => 'clean',
            'eventName'          => WebhookEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('campaign.sendwebhook', $sendWebhookAction);
    }
}
