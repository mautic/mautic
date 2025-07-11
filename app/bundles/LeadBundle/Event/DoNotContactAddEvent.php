<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

final class DoNotContactAddEvent extends Event
{
    public const ADD_DONOT_CONTACT = 'mautic.lead.add_donot_contact';

    public function __construct(
        private Lead $lead,
        private string $channel,
        private string $comments = '',
        private int $reason = DNC::BOUNCED,
        private bool $persist = true,
        private bool $checkCurrentStatus = true,
        private bool $override = true,
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

    public function getComments(): string
    {
        return $this->comments;
    }

    public function getReason(): int
    {
        return $this->reason;
    }

    public function isPersist(): bool
    {
        return $this->persist;
    }

    public function isCheckCurrentStatus(): bool
    {
        return $this->checkCurrentStatus;
    }

    public function isOverride(): bool
    {
        return $this->override;
    }
}
