<?php

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

class ContactIdentificationEvent extends Event
{
    private ?Lead $identifiedContact = null;

    /**
     * @var string
     */
    private $identifiedByChannel;

    public function __construct(
        private array $clickthrough,
    ) {
    }

    public function getClickthrough(): array
    {
        return $this->clickthrough;
    }

    /**
     * @param string $channel
     */
    public function setIdentifiedContact(Lead $contact, $channel): void
    {
        $this->identifiedContact   = $contact;
        $this->identifiedByChannel = $channel;

        $this->stopPropagation();
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifiedByChannel;
    }

    public function getIdentifiedContact(): ?Lead
    {
        return $this->identifiedContact;
    }
}
