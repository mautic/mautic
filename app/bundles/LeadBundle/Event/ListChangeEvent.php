<?php

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Contracts\EventDispatcher\Event;

class ListChangeEvent extends Event
{
    private Lead $lead;

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
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return LeadList
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * Returns batch array of leads.
     *
     * @return array|null
     */
    public function getLeads()
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
