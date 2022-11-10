<?php

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ListChangeEvent.
 */
class ListChangeEvent extends Event
{
    private $lead;
    private $leads;
    private $list;
    private $added;

    /**
     * ListChangeEvent constructor.
     *
     * @param      $leads
     * @param bool $added
     */
    public function __construct($leads, LeadList $list, $added = true)
    {
        if (is_array($leads)) {
            $this->leads = $leads;
        } else {
            $this->lead = $leads;
        }
        $this->list  = $list;
        $this->added = $added;
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
     * @return LeadList
     */
    public function getList()
    {
        return $this->list;
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
}
