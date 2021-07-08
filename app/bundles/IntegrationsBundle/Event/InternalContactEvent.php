<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

class InternalContactEvent extends Event
{
    /**
     * @var string
     */
    private $integrationName;

    /**
     * @var Lead
     */
    private $contact;

    public function __construct(string $integrationName, Lead $contact)
    {
        $this->integrationName = $integrationName;
        $this->contact         = $contact;
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
