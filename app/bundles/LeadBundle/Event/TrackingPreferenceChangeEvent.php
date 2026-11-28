<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a contact's choice about a tracking purpose is stored.
 */
final class TrackingPreferenceChangeEvent extends Event
{
    public const TRACKING_PREFERENCE_CHANGE = 'mautic.lead.tracking_preference_change';

    public function __construct(
        private readonly Lead $contact,
        private readonly string $purpose,
        private readonly bool $allowed,
        private readonly ?string $source = null,
    ) {
    }

    public function getContact(): Lead
    {
        return $this->contact;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }
}
