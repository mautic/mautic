<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

final class ContactIdentificationEvent extends Event
{
    private ?Lead $identifiedContact = null;

    private ?string $identifiedByChannel = null;

    public function __construct(
        private readonly array $clickthrough,
    ) {
    }

    public function getClickthrough(): array
    {
        return $this->clickthrough;
    }
    public function setIdentifiedContact(Lead $contact, string $channel): void
    {
        $this->identifiedContact   = $contact;
        $this->identifiedByChannel = $channel;

        $this->stopPropagation();
    }

    public function getIdentifier(): ?string
    {
        return $this->identifiedByChannel;
    }

    public function getIdentifiedContact(): ?Lead
    {
        return $this->identifiedContact;
    }
}
