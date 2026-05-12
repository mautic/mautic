<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

final class LeadGetCurrentEvent extends Event
{
    private ?Lead $contact = null;

    public function __construct(private ?Request $request = null)
    {
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function getContact(): ?Lead
    {
        return $this->contact;
    }

    public function setContact(?Lead $contact): void
    {
        $this->contact = $contact;
    }
}
