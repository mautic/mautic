<?php

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Contracts\EventDispatcher\Event;

class ListChangeEvent extends Event
{
    private ?Lead $lead = null;

    /**
     * @var Lead[]|null
     */
    private ?array $leads = null;

    /**
     * @param Lead[]|Lead $leads
     */
    public function __construct(
        Lead|array $leads,
        private LeadList $list,
        private bool $added = true,
        private ?\DateTime $date = null,
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

    public function getList(): LeadList
    {
        return $this->list;
    }

    /**
     * Returns batch array of leads.
     */
    public function getLeads(): ?array
    {
        return $this->leads;
    }

    public function wasAdded(): bool
    {
        return $this->added;
    }

    public function wasRemoved(): bool
    {
        return !$this->added;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }
}
