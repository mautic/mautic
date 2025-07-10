<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\WebhookBundle\Event\WebhookBuilderEvent;
use Mautic\WebhookBundle\Event\WebhookQueueEvent;
use Mautic\WebhookBundle\Model\WebhookModel;
use Mautic\WebhookBundle\WebhookEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WebhookSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private WebhookModel $webhookModel,
        private bool $includeDetails,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_SEND          => ['onEmailSend', 0],
            EmailEvents::EMAIL_ON_OPEN          => ['onEmailOpen', 0],
            WebhookEvents::WEBHOOK_ON_BUILD     => ['onWebhookBuild', 0],
            WebhookEvents::WEBHOOK_QUEUE_ON_ADD => ['onWebhookQueueOnAdd', 0],
        ];
    }

    /**
     * Add event triggers and actions.
     */
    public function onWebhookBuild(WebhookBuilderEvent $event): void
    {
        // add checkbox to the webhook form for new leads
        $mailSend= [
            'label'       => 'mautic.email.webhook.event.send',
            'description' => 'mautic.email.webhook.event.send_desc',
        ];
        $mailOpen = [
            'label'       => 'mautic.email.webhook.event.open',
            'description' => 'mautic.email.webhook.event.open_desc',
        ];

        // add it to the list
        $event->addEvent(EmailEvents::EMAIL_ON_SEND, $mailSend);
        $event->addEvent(EmailEvents::EMAIL_ON_OPEN, $mailOpen);
    }

    public function onEmailSend(EmailSendEvent $event): void
    {
        // Ignore test email sends.
        if ($event->isInternalSend() || null === $event->getLead()) {
            return;
        }

        $payload = [
            'email'       => $event->getEmail(),
            'contact'     => $event->getLead(),
            'contentHash' => $event->getContentHash(),
            'idHash'      => $event->getIdHash(),
            'subject'     => $event->getSubject(),
            'source'      => $event->getSource(),
            'headers'     => $event->getTextHeaders(),
        ];

        if ($this->includeDetails) {
            $payload['content'] = $event->getContent();
            $payload['tokens']  = $event->getTokens();
        }

        $this->webhookModel->queueWebhooksByType(EmailEvents::EMAIL_ON_SEND, $payload);
    }

    public function onEmailOpen(EmailOpenEvent $event): void
    {
        $this->webhookModel->queueWebhooksByType(
            EmailEvents::EMAIL_ON_OPEN,
            [
                'stat' => $event->getStat(),
            ],
            [
                'statDetails',
                'leadList',
                'emailDetails',
            ]
        );
    }

    public function onWebhookQueueOnAdd(WebhookQueueEvent $event): void
    {
        if ($this->includeDetails) {
            return;
        }

        $webhookQueue = $event->getWebhookQueue();
        $eventType    = $webhookQueue->getEvent()->getEventType();

        if (!in_array($eventType, [EmailEvents::EMAIL_ON_SEND, EmailEvents::EMAIL_ON_OPEN])) {
            return;
        }

        $payload = json_decode($webhookQueue->getPayload(), true);

        if (!is_array($payload)) {
            return;
        }

        if (EmailEvents::EMAIL_ON_SEND === $eventType) {
            unset($payload['email']['customHtml']);
            unset($payload['email']['plainText']);
        } elseif (EmailEvents::EMAIL_ON_OPEN === $eventType) {
            unset($payload['stat']['email']['customHtml']);
            unset($payload['stat']['email']['plainText']);
        }

        $webhookQueue->setPayload(json_encode($payload));
    }
}
