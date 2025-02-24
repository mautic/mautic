<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

final class InternalContactEvent extends Event
{
    public function __construct(
        private string $integrationName,
        private Lead $contact
    ) {
    }

    public function getIntegrationName(): string
    {
        return $this->integrationName;
    }

    public function getContact(): Lead
    {
        return $this->contact;
    }
}
