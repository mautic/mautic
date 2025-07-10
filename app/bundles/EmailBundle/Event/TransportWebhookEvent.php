<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event triggered when a transport service send Mautic a webhook request.
 */
final class TransportWebhookEvent extends Event
{
    private ?Response $response = null;

    public function __construct(
        private Request $request,
    ) {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }
}
