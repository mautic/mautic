<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Message\Traits;

use DateTime;
use DateTimeInterface;
use Mautic\MessengerBundle\Factory\MessengerRequestFactory;
use Mautic\MessengerBundle\Message\EmailHitNotification;
use Symfony\Component\HttpFoundation\Request;

trait MessageRequestTrait
{
    private string $eventTime; // The ISO-8601 date
    private array $request; //  Simplified interpretation of symfony request

    /** @return string The ISO-8601 date */
    public function getEventTime(): string
    {
        return $this->eventTime;
    }

    /**
     * @param string|DateTimeInterface $eventTime
     *
     * @return MessageRequestTrait|EmailHitNotification
     */
    public function setEventTime($eventTime): self
    {
        $eventTime ??= (new DateTime())->format('c');

        $this->eventTime = $eventTime instanceof DateTimeInterface
            ? $eventTime->format('c')
            : $eventTime
        ;

        return $this;
    }

    public function getRequest(): array
    {
        return $this->request;
    }

    public function getRequestObject(): Request
    {
        return MessengerRequestFactory::fromArray($this->request);
    }

    /**
     * @param array|Request $request
     */
    public function setRequest($request): self
    {
        $this->request = $request instanceof Request ? MessengerRequestFactory::toArray($request) : $request;

        return $this;
    }
}
