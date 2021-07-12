<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\LeadBundle\Entity\Company;
use Symfony\Component\EventDispatcher\Event;

class InternalCompanyEvent extends Event
{
    /**
     * @var string
     */
    private $integrationName;

    /**
     * @var Company
     */
    private $company;

    public function __construct(string $integrationName, Company $company)
    {
        $this->integrationName = $integrationName;
        $this->company         = $company;
    }

    public function getIntegrationName(): string
    {
        return $this->integrationName;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }
}
