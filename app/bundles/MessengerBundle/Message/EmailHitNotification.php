<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Message;

use Mautic\MessengerBundle\Message\Interfaces\RequestStatusInterface;
use Mautic\MessengerBundle\Message\Traits\MessageRequestTrait;
use Symfony\Component\HttpFoundation\Request;

class EmailHitNotification implements RequestStatusInterface
{
    use MessageRequestTrait;

    public function __construct(
        private string $statId,
        Request $request,
        \DateTime $eventTime = null
    ) {
        $this->setEventTime($eventTime ?? new \DateTime());
        $this->setRequest($request);
    }

    public function getStatId(): string
    {
        return $this->statId;
    }
}
