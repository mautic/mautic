<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Message\Traits;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\HttpFoundation\Request;

trait MessageRequestTrait
{
    private string $eventTime; // The ISO-8601 date
    /** @Serializer\Type(Symfony\Component\HttpFoundation\Request) */
    private Request $request; //  Simplified interpretation of symfony request
    private bool $isSynchronous = false;

    /** @return string The ISO-8601 date */
    public function getEventTime(): string
    {
        return $this->eventTime;
    }

    public function setEventTime(\DateTimeInterface $eventTime = null): self
    {
        $eventTime ??= (new \DateTimeImmutable())->format('c');

        $this->eventTime = $eventTime instanceof \DateTimeInterface
            ? $eventTime->format('c')
            : $eventTime;

        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
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
