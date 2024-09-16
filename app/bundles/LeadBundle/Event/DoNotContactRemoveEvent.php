<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

final class DoNotContactRemoveEvent extends Event
{
    public const REMOVE_DONOT_CONTACT = 'mautic.lead.remove_donot_contact';

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var string
     */
    private $channel;

    /**
     * @var bool
     */
    private $persist;

    public function __construct(Lead $lead, string $channel, bool $persist = true)
    {
        $this->lead    = $lead;
        $this->channel = $channel;
        $this->persist = $persist;
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
