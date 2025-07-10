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

    /**
     * @return array
     */
    public function getClickthrough()
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

    /**
     * @return Lead
     */
    public function getIdentifiedContact()
    {
        return $this->identifiedContact;
    }
}
