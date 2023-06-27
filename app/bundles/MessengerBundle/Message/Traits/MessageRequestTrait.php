<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Message\Traits;

use Mautic\MessengerBundle\Factory\MessengerRequestFactory;
use Symfony\Component\HttpFoundation\Request;

trait MessageRequestTrait
{
    private string $eventTime; // The ISO-8601 date
    /**
     * @var array<string,mixed>
     */
    private array $request; //  Simplified interpretation of symfony request
    private bool $isSynchronous = false;

    /** @return string The ISO-8601 date */
    public function getEventTime(): string
    {
        return $this->eventTime;
    }

    public function setEventTime(\DateTimeInterface $eventTime = null): self
    {
        $eventTime ??= (new \DateTime())->format('c');

        $this->eventTime = $eventTime instanceof \DateTimeInterface
            ? $eventTime->format('c')
            : $eventTime;

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function getRequest(): array
    {
        return $this->request;
    }

    public function getRequestObject(): Request
    {
        return MessengerRequestFactory::fromArray($this->request);
    }

    /**
     * @param array<string,mixed>|Request $request
     */
    public function setRequest(array|Request $request): self
    {
        $this->request = $request instanceof Request ? MessengerRequestFactory::toArray($request) : $request;

        return $this;
    }

    public function setIsSynchronousRequest(bool $isSynchronous = true): self
    {
        $this->isSynchronous = $isSynchronous;

        return $this;
    }

    public function isSynchronousRequest(): bool
    {
        return $this->isSynchronous;
    }
}
