<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

final class DoNotContactRemoveEvent extends Event
{
    public const REMOVE_DONOT_CONTACT = 'mautic.lead.remove_donot_contact';

    public function __construct(
        private readonly Lead $lead,
        private readonly string $channel,
        private readonly bool $persist = true,
    ) {
    }

    public function getLead(): Lead
    {
        return $this->lead;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getPersist(): bool
    {
        return $this->persist;
    }
}
