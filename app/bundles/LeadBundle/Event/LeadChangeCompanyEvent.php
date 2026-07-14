<?php

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

class LeadChangeCompanyEvent extends Event
{
    private ?Lead $lead = null;

    /**
     * @var Lead[]|null
     */
    private ?array $leads = null;

    /**
     * @param Lead|Lead[] $leads
     */
    public function __construct(
        Lead|array $leads,
        private readonly Company $company,
        private readonly bool $added = true,
    ) {
        if (is_array($leads)) {
            $this->leads = $leads;
        } else {
            $this->lead = $leads;
        }
    }

    /**
     * Returns the Lead entity.
     */
    public function getLead(): ?Lead
    {
        return $this->lead;
    }

    /**
     * Returns batch array of leads.
     */
    public function getLeads(): ?array
    {
        return $this->leads;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function wasAdded(): bool
    {
        return $this->added;
    }

    public function wasRemoved(): bool
    {
        return !$this->added;
    }
}
