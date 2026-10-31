<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

final class FilterEvent extends Event
{
    public const REMOVAL_REASON_MISSING_NUMBER = 'missing_number';

    /**
     * @var array<int>
     */
    private array $removed  = [];

    /**
     * @var array<int, string>
     */
    private array $removalReasons = [];

    /**
     * @param array<int, Lead> $contacts
     */
    public function __construct(
        private array $contacts,
    ) {
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
    public function getRemovedContacts(?string $reason = null): array
    {
        if (null !== $reason) {
            return array_values(array_filter(
                $this->removed,
                fn (int $contactId): bool => $reason === ($this->removalReasons[$contactId] ?? null),
            ));
        }

        return $this->removed;
    }

    public function removeContact(int $id, ?string $reason = null): void
    {
        $this->removed[] = $id;
        if (null !== $reason) {
            $this->removalReasons[$id] = $reason;
        }
        unset($this->contacts[$id]);
    }

    /**
     * @param array<int> $contacts
     */
    public function removeContacts(array $contacts, ?string $reason = null): void
    {
        foreach ($contacts as $contact) {
            $this->removeContact((int) $contact, $reason);
        }
    }
}
