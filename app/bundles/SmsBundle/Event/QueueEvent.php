<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

final class QueueEvent extends Event
{
    /**
     * @var array<int>
     */
    private array $queued  = [];

    /**
     * @param array<int, Lead>     $contacts
     * @param array<string, mixed> $options
     */
    public function __construct(private array $contacts, private readonly array $options)
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
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return array<int>
     */
    public function getQueuedContacts(): array
    {
        return $this->queued;
    }

    public function queueContact(int $id): void
    {
        array_push($this->queued, $id);
        unset($this->contacts[$id]);
    }

    /**
     * @param array<int> $contacts
     */
    public function queueContacts(array $contacts): void
    {
        foreach ($contacts as $contact) {
            $this->queueContact((int) $contact);
        }
    }
}
