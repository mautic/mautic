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
        private Company $company,
        private bool $added = true
    ) {
        if (is_array($leads)) {
            $this->leads = $leads;
        } else {
            $this->lead = $leads;
        }
    }

    /**
     * Returns the Lead entity.
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * Returns batch array of leads.
     *
     * @return array
     */
    public function getLeads()
    {
        return $this->leads;
    }

    /**
     * @return Company/Company
     */
    public function getCompany()
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
