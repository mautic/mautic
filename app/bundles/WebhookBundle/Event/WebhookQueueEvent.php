<?php

namespace Mautic\WebhookBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;

final class WebhookQueueEvent extends CommonEvent
{
    /**
     * @param bool $isNew
     */
    public function __construct(
        WebhookQueue $webhookQueue,
        private Webhook $webhook,
        $isNew = false,
    ) {
        $this->entity  = $webhookQueue;
        $this->isNew   = $isNew;
    }

    /**
     * Returns the WebhookQueue entity.
     *
     * @return WebhookQueue
     */
    public function getWebhookQueue()
    {
        return $this->entity;
    }

    /**
     * Sets the WebhookQueue entity.
     */
    public function setWebhookQueue(WebhookQueue $webhookQueue): void
    {
        $this->entity = $webhookQueue;
    }

    /**
     * Returns the Webhook entity.
     */
    public function getWebhook(): Webhook
    {
        return $this->webhook;
    }

    /**
     * Sets the Webhook entity.
     */
    public function setWebhook(Webhook $webhook): void
    {
        $this->webhook = $webhook;
    }
}
