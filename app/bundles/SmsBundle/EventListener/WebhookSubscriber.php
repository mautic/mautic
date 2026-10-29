<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\SmsBundle\Event\SmsSendEvent;
use Mautic\SmsBundle\SmsEvents;
use Mautic\WebhookBundle\Event\WebhookBuilderEvent;
use Mautic\WebhookBundle\Model\WebhookModel;

final readonly class WebhookSubscriber
{
    public function __construct(
        private WebhookModel $webhookModel,
    ) {
    }

    /**
     * Add event triggers and actions.
     */
    #[AsEventListener(event: WebhookBuilderEvent::class)]
    public function onWebhookBuild(WebhookBuilderEvent $event): void
    {
        $event->addEvent(
            SmsEvents::SMS_ON_SEND,
            [
                'label'       => 'mautic.sms.webhook.event.send',
                'description' => 'mautic.sms.webhook.event.send_desc',
            ]
        );
    }

    #[AsEventListener(event: SmsSendEvent::class)]
    public function onSend(SmsSendEvent $event): void
    {
        $this->webhookModel->queueWebhooksByType(
            SmsEvents::SMS_ON_SEND,
            [
                'smsId'   => $event->getSmsId(),
                'contact' => $event->getLead(),
                'content' => $event->getContent(),
            ]
        );
    }
}
