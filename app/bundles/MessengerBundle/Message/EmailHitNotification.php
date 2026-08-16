<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Message;

use Mautic\MessengerBundle\Message\Traits\MessageRequestTrait;
use Symfony\Component\HttpFoundation\Request;

final class EmailHitNotification
{
    use MessageRequestTrait;

    public function __construct(
<<<<<<< HEAD
        private string $statId,
=======
        private readonly string $statId,
>>>>>>> 1ebbe49a7c (avoid trait override)
        private Request $request,
        ?\DateTimeInterface $eventTime = null,
    ) {
        $this->setEventTime($eventTime ?? new \DateTimeImmutable());
    }

    public function getStatId(): string
    {
        return $this->statId;
    }
}
