<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Event;

use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class BounceEmailEvent extends Event
{
    /**
     * @param Lead[] $contacts
     */
    public function __construct(
        private Request $request,
        private Email $email,
        private array $contacts
    ) {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getContacts(): array
    {
        return $this->contacts;
    }
}
