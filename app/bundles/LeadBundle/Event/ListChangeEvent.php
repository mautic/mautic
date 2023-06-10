<?php

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class ListChangeEvent.
 */
class ListChangeEvent extends Event
{
    private $lead;
    private $leads;

    /**
     * ListChangeEvent constructor.
     *
     * @param bool $added
     */
    public function __construct($leads, private LeadList $list, private $added = true, private ?\DateTime $date = null)
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
     * @return LeadList
     */
    public function getList()
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

    /**
     * @return bool
     */
    public function wasAdded()
    {
        return $this->added;
    }

    /**
     * @return bool
     */
    public function wasRemoved()
    {
        return !$this->added;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }
}
