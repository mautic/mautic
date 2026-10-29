<?php

namespace Mautic\WebhookBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\WebhookBundle\Form\Type\CampaignEventSendWebhookType;
use Mautic\WebhookBundle\Helper\CampaignHelper;
use Mautic\WebhookBundle\WebhookEvents;

final readonly class CampaignSubscriber
{
    public function __construct(
        private CampaignHelper $campaignHelper,
    ) {
    }

    #[AsEventListener(event: WebhookEvents::ON_CAMPAIGN_BATCH_ACTION)]
    public function onCampaignTriggerAction(PendingEvent $event): void
    {
        if (!$event->checkContext('campaign.sendwebhook')) {
            return;
        }

        $config = $event->getEvent()->getProperties();

        foreach ($event->getPending() as $log) {
            try {
                $this->campaignHelper->fireWebhook($config, $log->getLead());
                $event->pass($log);
            } catch (\Exception $e) {
                $event->fail($log, $e->getMessage());
            }
        }
    }

    /**
     * Add event triggers and actions.
     */
    #[AsEventListener(event: CampaignBuilderEvent::class)]
    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $sendWebhookAction = [
            'label'              => 'mautic.webhook.event.sendwebhook',
            'description'        => 'mautic.webhook.event.sendwebhook_desc',
            'formType'           => CampaignEventSendWebhookType::class,
            'formTypeCleanMasks' => 'clean',
            'batchEventName'     => WebhookEvents::ON_CAMPAIGN_BATCH_ACTION,
        ];
        $event->addAction('campaign.sendwebhook', $sendWebhookAction);
    }
}
