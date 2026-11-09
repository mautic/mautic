<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class ContactLastActiveUpdatedEvent extends Event
{
    public function __construct(
        private int $contactId,
        private \DateTimeInterface $lastActive,
        private ?\DateTimeInterface $previousLastActive,
    ) {
    }

    public function getContactId(): int
    {
        return $this->contactId;
    }

    public function getLastActive(): \DateTimeInterface
    {
        return $this->lastActive;
    }

    public function getPreviousLastActive(): ?\DateTimeInterface
    {
        return $this->previousLastActive;
    }
}
