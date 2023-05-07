<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Message;

use DateTime;
use Mautic\MessengerBundle\Message\Traits\MessageRequestTrait;
use Symfony\Component\HttpFoundation\Request;

class EmailHitNotification
{
    use MessageRequestTrait;

    private string $statId;

    public function __construct(
        string $statId,
        Request $request,
        ?DateTime $eventTime = null
    ) {
        $this->setEventTime($eventTime ?? new DateTime());
        $this->setRequest($request);
        $this->statId = $statId;
    }

    public function getStatId(): string
    {
        return $this->statId;
    }
}
