<?php

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

class LeadChangeCompanyEvent extends Event
{
    private $lead;
    private ?array $leads = null;

    public function __construct($leads, private Company $company, private $added = true)
    {
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

    /**
     * @return bool
     */
    public function wasAdded()
    {
        return $this->added;
    }

    public function wasRemoved(): bool
    {
        return !$this->added;
    }
}
