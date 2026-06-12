<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

final class FilterEvent extends Event
{
    /**
     * @var array<int>
     */
    private array $removed  = [];

    /**
     * @param array<int, Lead> $contacts
     */
    public function __construct(private array $contacts)
    {
    }

    /**
     * @return array<int, Lead>
     */
    public function getContacts(): array
    {
        return $this->contacts;
    }

    /**
     * @return array<int>
     */
    public function getRemovedContacts(): array
    {
        return $this->removed;
    }

    public function removeContact(int $id): void
    {
        array_push($this->removed, $id);
        unset($this->contacts[$id]);
    }

    /**
     * @param array<int> $contacts
     */
    public function removeContacts(array $contacts): void
    {
        foreach ($contacts as $contact) {
            $this->removeContact((int) $contact);
        }
    }
}
