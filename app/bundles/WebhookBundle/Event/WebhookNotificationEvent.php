<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Event;

use Mautic\WebhookBundle\Entity\Webhook;
use Symfony\Contracts\EventDispatcher\Event;

class WebhookNotificationEvent extends Event
{
    private bool $canSend = true;

    public function __construct(private Webhook $webhook)
    {
    }

    public function getWebhook(): Webhook
    {
        return $this->webhook;
    }

    public function setCanSend(bool $canSend): void
    {
        $this->canSend  = $canSend;
    }

    public function canSend(): bool
    {
        return $this->canSend;
    }
}
