<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Message;

use Mautic\MessengerBundle\Message\Traits\MessageRequestTrait;
use Symfony\Component\HttpFoundation\Request;

class EmailHitNotification
{
    use MessageRequestTrait;

    public function __construct(
        private string $statId,
        private Request $request,
        \DateTimeInterface $eventTime = null
    ) {
        $this->setEventTime($eventTime ?? new \DateTimeImmutable());
    }

    public function getStatId(): string
    {
        return $this->statId;
    }
}
