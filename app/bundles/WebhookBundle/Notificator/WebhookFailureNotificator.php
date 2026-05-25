<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Notificator;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\WebhookBundle\Entity\Webhook;
use Symfony\Contracts\Translation\TranslatorInterface;

class WebhookFailureNotificator
{
    public function __construct(
        private WebhookNotificationSender $sender,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param string $reason Translatable key
     */
    public function send(Webhook $webhook, string $reason): void
    {
        $subject = $this->translator->trans('mautic.webhook.failing', [
            '%webhook%' => $webhook->getName(),
        ]);
        $reason  = $this->translator->trans($reason);
        $details = [
            'reason'              => $reason,
            'webhook'             => $webhook,
            'failing_since'       => $webhook->getUnHealthySince()->format(DateTimeHelper::FORMAT_DB),
            'signature_from_name' => $this->sender->getFromNameForSignature(),
        ];

        $this->sender->send($webhook, $subject, '@MauticWebhook/Notifications/webhook-failing.html.twig', $details);
    }
}
