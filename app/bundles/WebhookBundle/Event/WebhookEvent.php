<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\WebhookBundle\Entity\Webhook;

class WebhookEvent extends CommonEvent
{
    /**
     * @var Webhook
     */
    protected $entity;

    public function __construct(
        Webhook $webhook,
        protected bool $isNew = false,
        private string $reason = '',
    ) {
        $this->entity = $webhook;
    }

    /**
     * Returns the Webhook entity.
     *
     * @return Webhook
     */
    public function getWebhook()
    {
        return $this->entity;
    }

    /**
     * Sets the Webhook entity.
     */
    public function setWebhook(Webhook $webhook): void
    {
        $this->entity = $webhook;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
