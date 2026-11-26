<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class ContactPreCleanupDeleteEvent extends Event
{
    /**
     * @param int[] $contactIds
     */
    public function __construct(
        private readonly array $contactIds,
        private readonly bool $gdpr,
    ) {
    }

    /**
     * @return int[]
     */
    public function getContactIds(): array
    {
        return $this->contactIds;
    }

    public function isGdpr(): bool
    {
        return $this->gdpr;
    }
}
