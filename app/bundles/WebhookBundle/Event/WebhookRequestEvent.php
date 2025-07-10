<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

class WebhookRequestEvent extends Event
{
    public function __construct(
        private Lead $contact,
        private string $url,
        private array $headers,
        private array $payload,
    ) {
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    public function getContact(): Lead
    {
        return $this->contact;
    }
}
